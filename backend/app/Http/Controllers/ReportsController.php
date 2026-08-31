<?php

namespace App\Http\Controllers;

use App\Models\PracticeSession;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentWordProgress;
use App\Models\Teacher;
use App\Models\User;
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

        $studentIds = $classes->flatMap(fn ($class) => $class->students->pluck('id'))->unique()->values()->all();
        $levels = $classes->flatMap(fn ($class) => $class->vocabularyLevels)->unique('id')->values();
        $levelWordIds = $levels->mapWithKeys(fn ($level) => [$level->id => $level->words->pluck('id')->all()]);
        $teacherWordIds = $levelWordIds->flatten()->unique()->values()->all();
        $teacherProgress = StudentWordProgress::query()
            ->whereIn('student_id', $studentIds ?: [0])
            ->whereIn('vocabulary_word_id', $teacherWordIds ?: [0])
            ->get();

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
                'vocabulary_levels_count' => $levels->count(),
                'top_students' => Student::query()
                    ->whereIn('id', $studentIds ?: [0])
                    ->with('user:id,name,email')
                    ->get()
                    ->map(function ($student) use ($teacherProgress) {
                        $progress = $teacherProgress->where('student_id', $student->id);

                        return [
                            'student_id' => $student->id,
                            'name' => $student->user?->name,
                            'email' => $student->user?->email,
                            'average_mastery' => round((float) ($progress->avg('mastery_percent') ?? 0), 2),
                            'total_xp' => (int) ($student->total_xp ?? 0),
                        ];
                    })
                    ->sortByDesc(fn ($student) => [$student['average_mastery'], $student['total_xp']])
                    ->take(5)
                    ->values(),
                'vocabulary_level_stats' => $levels->map(function ($level) use ($levelWordIds, $teacherProgress) {
                    $wordIds = $levelWordIds->get($level->id, []);
                    $progress = $teacherProgress->whereIn('vocabulary_word_id', $wordIds);

                    return [
                        'level_id' => $level->id,
                        'title' => $level->title,
                        'total_words' => count($wordIds),
                        'average_mastery' => round((float) ($progress->avg('mastery_percent') ?? 0), 2),
                        'mastered_words' => $progress->where('mastery_percent', '>=', 100)->count(),
                        'unmastered_words' => $progress->where('mastery_percent', '<', 100)->count(),
                    ];
                })->values(),
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
        $completedSessions = PracticeSession::query()->whereNotNull('completed_at')->get(['started_at', 'completed_at']);
        $averageSessionDuration = $completedSessions->count() > 0
            ? round($completedSessions->avg(fn ($session) => $session->started_at->diffInSeconds($session->completed_at)), 2)
            : 0;
        $rangeStart = now()->startOfDay()->subDays(6);
        $sessionCounts = PracticeSession::query()->where('started_at', '>=', $rangeStart)->get(['started_at'])
            ->countBy(fn ($session) => $session->started_at->toDateString());
        $userCounts = User::query()->where('created_at', '>=', $rangeStart)->get(['created_at'])
            ->countBy(fn ($item) => $item->created_at->toDateString());
        $usageTimeseries = collect(range(0, 6))->map(function ($offset) use ($rangeStart, $sessionCounts, $userCounts) {
            $date = $rangeStart->copy()->addDays($offset)->toDateString();

            return [
                'date' => $date,
                'practice_sessions' => $sessionCounts->get($date, 0),
                'new_users' => $userCounts->get($date, 0),
            ];
        });
        $classes = SchoolClass::query()->with(['teacher.user:id,name', 'students:id', 'vocabularyLevels.words:id,vocabulary_level_id'])->get();
        $allProgress = StudentWordProgress::all();
        $allSessions = PracticeSession::all();
        $classPerformance = $classes->map(function ($class) use ($allProgress, $allSessions) {
            $studentIds = $class->students->pluck('id')->all();
            $wordIds = $class->vocabularyLevels->flatMap(fn ($level) => $level->words->pluck('id'))->unique();
            $progress = $allProgress->whereIn('student_id', $studentIds)->whereIn('vocabulary_word_id', $wordIds);
            $sessions = $allSessions->whereIn('student_id', $studentIds);

            return [
                'class_id' => $class->id,
                'class_name' => $class->name,
                'teacher_name' => $class->teacher?->user?->name,
                'enrolled_students' => count($studentIds),
                'average_mastery' => round((float) ($progress->avg('mastery_percent') ?? 0), 2),
                'practice_sessions' => $sessions->count(),
                'average_practice_score' => round((float) ($sessions->avg('score_percent') ?? 0), 2),
            ];
        });
        $teacherRankings = Teacher::query()->with(['user:id,name', 'schoolClasses.students:id', 'schoolClasses.vocabularyLevels.words:id,vocabulary_level_id'])->get()
            ->map(function ($teacher) use ($allProgress) {
                $studentIds = $teacher->schoolClasses->flatMap(fn ($class) => $class->students->pluck('id'))->unique()->values()->all();
                $wordIds = $teacher->schoolClasses
                    ->flatMap(fn ($class) => $class->vocabularyLevels)
                    ->unique('id')
                    ->flatMap(fn ($level) => $level->words->pluck('id'))
                    ->unique();
                $averageMastery = $allProgress->whereIn('student_id', $studentIds)->whereIn('vocabulary_word_id', $wordIds)->avg('mastery_percent') ?? 0;

                return [
                    'teacher_id' => $teacher->id,
                    'name' => $teacher->user?->name,
                    'class_count' => $teacher->schoolClasses->count(),
                    'student_count' => count($studentIds),
                    'average_mastery' => round((float) $averageMastery, 2),
                ];
            })
            ->sortByDesc('average_mastery')
            ->take(5)
            ->values();

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
                'average_session_duration' => $averageSessionDuration,
                'average_session_duration_unit' => 'seconds',
                'total_xp' => (int) Student::sum('total_xp'),
                'usage_timeseries' => $usageTimeseries,
                'class_performance' => $classPerformance,
                'teacher_rankings' => $teacherRankings,
                'feature_usage' => [
                    ['feature' => 'learning_progress_attempts', 'count' => (int) StudentWordProgress::sum('attempts')],
                    ['feature' => 'practice_sessions', 'count' => PracticeSession::count()],
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
