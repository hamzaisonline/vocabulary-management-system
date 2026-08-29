<?php

namespace App\Http\Controllers;

use App\Models\PracticeSession;
use App\Models\SchoolClass;
use App\Models\StudentWordProgress;
use App\Models\VocabularyLevel;
use App\Models\VocabularyWord;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function student(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->role?->name === 'student', 403);

        $student = $user->student;
        abort_if(! $student, 403);

        $accessibleLevelIds = VocabularyLevel::query()
            ->whereHas('schoolClasses.students', function ($query) use ($student) {
                $query->where('students.id', $student->id);
            })
            ->pluck('id')
            ->all();

        $accessibleWordIds = VocabularyWord::query()
            ->whereHas('vocabularyLevel.schoolClasses.students', function ($query) use ($student) {
                $query->where('students.id', $student->id);
            })
            ->pluck('id')
            ->all();

        $progressRows = StudentWordProgress::query()
            ->where('student_id', $student->id)
            ->whereIn('vocabulary_word_id', $accessibleWordIds ?: [0])
            ->get();

        $progressByWordId = $progressRows->keyBy('vocabulary_word_id');

        $masteredWords = $progressRows->filter(fn ($row) => (int) $row->mastery_percent >= 100)->count();
        $unmasteredWords = $progressRows->filter(fn ($row) => (int) $row->mastery_percent < 100)->count();

        $completedLevels = 0;
        foreach (VocabularyLevel::query()->whereIn('id', $accessibleLevelIds)->with('words')->get() as $level) {
            $levelWordIds = $level->words->pluck('id')->all();
            if ($levelWordIds === []) {
                continue;
            }

            $allMastered = collect($levelWordIds)->every(fn ($wordId) => (int) ($progressByWordId[$wordId]->mastery_percent ?? 0) >= 100);
            if ($allMastered) {
                $completedLevels++;
            }
        }

        $averageMastery = $accessibleWordIds === [] ? 0 : round($progressRows->avg('mastery_percent') ?? 0, 2);

        $recentPracticeSessions = PracticeSession::query()
            ->where('student_id', $student->id)
            ->with('vocabularyLevel')
            ->orderByDesc('started_at')
            ->limit(5)
            ->get()
            ->map(fn ($session) => [
                'id' => $session->id,
                'vocabulary_level_id' => $session->vocabulary_level_id,
                'level_title' => $session->vocabularyLevel?->title,
                'started_at' => $session->started_at?->toISOString(),
                'completed_at' => $session->completed_at?->toISOString(),
                'score_percent' => (int) $session->score_percent,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'total_xp' => (int) ($student->total_xp ?? 0),
                'accessible_vocabulary_levels_count' => count($accessibleLevelIds),
                'completed_vocabulary_levels_count' => $completedLevels,
                'current_unmastered_words_count' => $unmasteredWords,
                'mastered_words_count' => $masteredWords,
                'recent_practice_sessions' => $recentPracticeSessions,
                'recent_reviewable_words_count' => $unmasteredWords,
                'average_mastery_across_accessible_vocabulary' => $averageMastery,
            ],
        ]);
    }

    public function teacher(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->role?->name === 'teacher', 403);

        $teacher = $user->teacher;
        abort_if(! $teacher, 403);

        $classIds = SchoolClass::query()->where('teacher_id', $teacher->id)->pluck('id');
        $classes = SchoolClass::query()
            ->where('teacher_id', $teacher->id)
            ->with(['students.user', 'vocabularyLevels'])
            ->get();

        $studentIds = $classes->flatMap(fn ($class) => $class->students->pluck('id')->all())->unique()->values()->all();

        $levelIds = $classes->flatMap(fn ($class) => $class->vocabularyLevels->pluck('id')->all())->unique()->values()->all();

        $wordIds =
            \
            \
            $classes->flatMap(fn ($class) => $class->vocabularyLevels->flatMap(fn ($level) => $level->words->pluck('id')->all()))
                ->unique()->values()->all();

        $progressRows = StudentWordProgress::query()
            ->whereIn('student_id', $studentIds ?: [0])
            ->whereIn('vocabulary_word_id', $wordIds ?: [0])
            ->get();

        $averageStudentProgress = $progressRows->count() > 0 ? round($progressRows->avg('mastery_percent') ?? 0, 2) : 0;

        $recentPracticeActivity = \App\Models\PracticeSession::query()
            ->whereIn('student_id', $studentIds ?: [0])
            ->with('student.user', 'vocabularyLevel')
            ->orderByDesc('started_at')
            ->limit(10)
            ->get()
            ->map(fn ($session) => [
                'id' => $session->id,
                'student_name' => $session->student?->user?->name,
                'level_title' => $session->vocabularyLevel?->title,
                'started_at' => $session->started_at?->toISOString(),
                'score_percent' => (int) $session->score_percent,
            ]);

        $classSummaries = $classes->map(function ($class) use ($progressRows) {
            $studentIdsForClass = $class->students->pluck('id')->all();
            $wordIdsForClass = $class->vocabularyLevels->flatMap(fn ($level) => $level->words->pluck('id')->all())->unique()->values()->all();
            $classProgress = $progressRows->filter(fn ($row) => in_array($row->student_id, $studentIdsForClass, true) && in_array($row->vocabulary_word_id, $wordIdsForClass, true));

            return [
                'class_id' => $class->id,
                'name' => $class->name,
                'student_count' => $class->students->count(),
                'assigned_vocabulary_level_count' => $class->vocabularyLevels->count(),
                'average_mastery' => $classProgress->count() > 0 ? round($classProgress->avg('mastery_percent') ?? 0, 2) : 0,
                'mastered_words' => $classProgress->filter(fn ($row) => (int) $row->mastery_percent >= 100)->count(),
                'unmastered_words' => $classProgress->filter(fn ($row) => (int) $row->mastery_percent < 100)->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'total_classes' => $classes->count(),
                'total_enrolled_students' => count($studentIds),
                'total_assigned_vocabulary_levels' => count($levelIds),
                'average_student_progress' => $averageStudentProgress,
                'recently_active_students' => collect($studentIds)->map(fn ($studentId) => [
                    'student_id' => $studentId,
                    'last_activity' => StudentWordProgress::query()->where('student_id', $studentId)->max('last_practiced_at')
                        ?->toISOString(),
                ])->values()->all(),
                'recent_practice_activity' => $recentPracticeActivity,
                'class_summaries' => $classSummaries,
            ],
        ]);
    }

    public function admin(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->role?->name === 'admin', 403);

        $totalUsers = \App\Models\User::count();
        $totalStudents = \App\Models\Student::count();
        $totalTeachers = \App\Models\Teacher::count();
        $totalClasses = SchoolClass::count();
        $totalLevels = VocabularyLevel::count();
        $totalWords = VocabularyWord::count();
        $totalPracticeSessions = \App\Models\PracticeSession::count();
        $averageMastery = StudentWordProgress::avg('mastery_percent') ?? 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => $totalUsers,
                'total_students' => $totalStudents,
                'total_teachers' => $totalTeachers,
                'total_classes' => $totalClasses,
                'total_vocabulary_levels' => $totalLevels,
                'total_vocabulary_words' => $totalWords,
                'total_practice_sessions' => $totalPracticeSessions,
                'average_student_mastery' => round((float) $averageMastery, 2),
                'recently_created_users' => \App\Models\User::query()->orderByDesc('created_at')->limit(5)->get()->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at?->toISOString(),
                ]),
                'recently_created_classes' => SchoolClass::query()->orderByDesc('created_at')->limit(5)->get()->map(fn ($class) => [
                    'id' => $class->id,
                    'name' => $class->name,
                    'created_at' => $class->created_at?->toISOString(),
                ]),
            ],
        ]);
    }
}
