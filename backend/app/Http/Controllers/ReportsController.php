<?php

namespace App\Http\Controllers;

use App\Models\PracticeSession;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentWordProgress;
use App\Models\VocabularyLevel;
use App\Models\VocabularyWord;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function teacher(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->role?->name === 'teacher', 403);

        $teacher = $user->teacher;
        abort_if(! $teacher, 403);

        $classes = SchoolClass::query()
            ->where('teacher_id', $teacher->id)
            ->with(['students', 'vocabularyLevels.words'])
            ->get();

        $studentIds = $classes->flatMap(fn ($class) => $class->students->pluck('id')->all())->unique()->values()->all();

        $classPerformance = $classes->map(function ($class) use ($studentIds) {
            $classStudentIds = $class->students->pluck('id')->all();
            $wordIds = $class->vocabularyLevels->flatMap(fn ($level) => $level->words->pluck('id')->all())->unique()->values()->all();
            $progressRows = StudentWordProgress::query()
                ->whereIn('student_id', $classStudentIds ?: [0])
                ->whereIn('vocabulary_word_id', $wordIds ?: [0])
                ->get();

            return [
                'class_id' => $class->id,
                'class_name' => $class->name,
                'student_count' => $class->students->count(),
                'average_mastery' => $progressRows->count() > 0 ? round($progressRows->avg('mastery_percent') ?? 0, 2) : 0,
                'mastered_words' => $progressRows->filter(fn ($row) => (int) $row->mastery_percent >= 100)->count(),
                'unmastered_words' => $progressRows->filter(fn ($row) => (int) $row->mastery_percent < 100)->count(),
                'practice_session_count' => PracticeSession::query()->whereIn('student_id', $classStudentIds ?: [0])->count(),
                'average_practice_score' => PracticeSession::query()->whereIn('student_id', $classStudentIds ?: [0])->avg('score_percent') ?? 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'class_performance' => $classPerformance,
                'students_per_class' => $classes->map(fn ($class) => [
                    'class_id' => $class->id,
                    'class_name' => $class->name,
                    'student_count' => $class->students->count(),
                ]),
                'average_mastery_per_class' => $classPerformance->map(fn ($item) => [
                    'class_id' => $item['class_id'],
                    'class_name' => $item['class_name'],
                    'average_mastery' => $item['average_mastery'],
                ]),
                'practice_session_counts' => $classes->map(fn ($class) => [
                    'class_id' => $class->id,
                    'class_name' => $class->name,
                    'practice_session_count' => PracticeSession::query()->whereIn('student_id', $class->students->pluck('id')->all() ?: [0])->count(),
                ]),
                'average_practice_score' => PracticeSession::query()->whereIn('student_id', $studentIds ?: [0])->avg('score_percent') ?? 0,
            ],
        ]);
    }

    public function admin(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->role?->name === 'admin', 403);

        $roleCounts = Role::query()->withCount('users')->get()->map(fn ($role) => [
            'role' => $role->name,
            'user_count' => $role->users_count,
        ]);

        $studentEnrollmentCounts = Student::query()->withCount('schoolClasses')->get()->sum('school_classes_count');
        $averageMastery = StudentWordProgress::avg('mastery_percent') ?? 0;
        $masteredWords = StudentWordProgress::query()->where('mastery_percent', '>=', 100)->count();
        $unmasteredWords = StudentWordProgress::query()->where('mastery_percent', '<', 100)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'user_counts_by_role' => $roleCounts,
                'class_counts' => [
                    'total_classes' => SchoolClass::count(),
                    'total_students_enrolled' => $studentEnrollmentCounts,
                ],
                'vocabulary_usage' => [
                    'total_vocabulary_levels' => VocabularyLevel::count(),
                    'total_vocabulary_words' => VocabularyWord::count(),
                ],
                'average_mastery' => round((float) $averageMastery, 2),
                'practice_activity' => [
                    'total_practice_sessions' => PracticeSession::count(),
                    'average_practice_score' => PracticeSession::avg('score_percent') ?? 0,
                ],
                'completion_metrics' => [
                    'mastered_words' => $masteredWords,
                    'unmastered_words' => $unmasteredWords,
                ],
            ],
        ]);
    }

    public function student(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->role?->name === 'student', 403);

        $student = $user->student;
        abort_if(! $student, 403);

        $progressRows = StudentWordProgress::query()->where('student_id', $student->id)->get();
        $accessibleLevelIds = VocabularyLevel::query()
            ->whereHas('schoolClasses.students', function ($query) use ($student) {
                $query->where('students.id', $student->id);
            })
            ->pluck('id')
            ->all();

        $completedLevels = 0;
        foreach (VocabularyLevel::query()->whereIn('id', $accessibleLevelIds)->with('words')->get() as $level) {
            $levelWordIds = $level->words->pluck('id')->all();
            if ($levelWordIds === []) {
                continue;
            }

            $allMastered = collect($levelWordIds)->every(fn ($wordId) => (int) ($progressRows->firstWhere('vocabulary_word_id', $wordId)?->mastery_percent ?? 0) >= 100);
            if ($allMastered) {
                $completedLevels++;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_xp' => (int) ($student->total_xp ?? 0),
                'average_mastery' => $progressRows->count() > 0 ? round($progressRows->avg('mastery_percent') ?? 0, 2) : 0,
                'completed_levels' => $completedLevels,
                'current_levels' => count($accessibleLevelIds),
                'practice_statistics' => [
                    'total_sessions' => PracticeSession::query()->where('student_id', $student->id)->count(),
                    'average_score' => PracticeSession::query()->where('student_id', $student->id)->avg('score_percent') ?? 0,
                    'mastered_words' => $progressRows->filter(fn ($row) => (int) $row->mastery_percent >= 100)->count(),
                    'unmastered_words' => $progressRows->filter(fn ($row) => (int) $row->mastery_percent < 100)->count(),
                ],
            ],
        ]);
    }
}
