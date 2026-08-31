<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentProgressUpdateRequest;
use App\Http\Requests\StudentReviewUpdateRequest;
use App\Models\Student;
use App\Models\StudentWordProgress;
use App\Models\VocabularyLevel;
use App\Models\VocabularyWord;
use App\Services\StudentProgressService;
use Illuminate\Http\Request;

class StudentProgressController extends Controller
{
    public function __construct(protected StudentProgressService $progressService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->role?->name === 'student', 403);

        $student = Student::with('schoolClasses.vocabularyLevels.words')->findOrFail($user->student?->id);

        $levels = $student->schoolClasses()
            ->with('vocabularyLevels.words')
            ->get()
            ->flatMap(fn ($class) => $class->vocabularyLevels)
            ->unique('id');

        $payload = [];

        foreach ($levels as $level) {
            $levelWords = $level->words()->with(['studentProgress' => function ($query) use ($student) {
                $query->where('student_id', $student->id);
            }])->get();

            $totalWords = $levelWords->count();
            $mastered = 0;
            $totalPercent = 0;

            foreach ($levelWords as $word) {
                $record = $word->studentProgress->first();
                $mastery = (int) ($record->mastery_percent ?? 0);
                $totalPercent += $mastery;

                if ($mastery >= 100) {
                    $mastered++;
                }
            }

            $progressPercent = $totalWords > 0 ? round($totalPercent / $totalWords) : 0;
            $completed = $totalWords > 0 && $mastered === $totalWords;

            $payload[] = [
                'id' => $level->id,
                'title' => $level->title,
                'description' => $level->description,
                'total_words' => $totalWords,
                'mastered_words' => $mastered,
                'progress_percent' => $progressPercent,
                'completed' => $completed,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_xp' => (int) $student->total_xp,
                'levels' => $payload,
            ],
        ]);
    }

    public function levelProgress(Request $request, VocabularyLevel $vocabularyLevel)
    {
        $user = $request->user();
        abort_unless($user && $user->role?->name === 'student', 403);

        $student = $user->student;
        abort_if(! $student, 403);

        $hasAccess = $student->schoolClasses()
            ->whereHas('vocabularyLevels', function ($query) use ($vocabularyLevel) {
                $query->where('vocabulary_levels.id', $vocabularyLevel->id);
            })
            ->exists();

        abort_unless($hasAccess, 403);

        $words = $vocabularyLevel->words()->with(['studentProgress' => function ($query) use ($student) {
            $query->where('student_id', $student->id);
        }])->get();

        $payloadWords = $words->map(function ($word) use ($student) {
            $record = $word->studentProgress->first();

            return [
                'id' => $word->id,
                'word' => $word->word,
                'translation' => $word->translation,
                'example' => $word->example,
                'notes' => $word->notes,
                'mastery_percent' => (int) ($record->mastery_percent ?? 0),
                'attempts' => (int) ($record->attempts ?? 0),
                'correct_attempts' => (int) ($record->correct_attempts ?? 0),
                'completed' => (int) ($record->mastery_percent ?? 0) >= 100,
            ];
        });

        $levelTotal = $payloadWords->count();
        $mastered = $payloadWords->filter(fn ($word) => $word['mastery_percent'] >= 100)->count();
        $totalMastery = $payloadWords->sum('mastery_percent');
        $overall = $levelTotal > 0 ? round($totalMastery / $levelTotal) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $vocabularyLevel->id,
                'title' => $vocabularyLevel->title,
                'description' => $vocabularyLevel->description,
                'difficulty' => $vocabularyLevel->difficulty,
                'stage' => $vocabularyLevel->stage,
                'words' => $payloadWords,
                'summary' => [
                    'total_words' => $levelTotal,
                    'mastered_words' => $mastered,
                    'progress_percent' => $overall,
                    'completed' => $levelTotal > 0 && $mastered === $levelTotal,
                ],
            ],
        ]);
    }

    public function updateWordProgress(StudentProgressUpdateRequest $request, VocabularyWord $vocabularyWord)
    {
        $user = $request->user();
        abort_unless($user && $user->role?->name === 'student', 403);

        $student = $user->student;
        abort_if(! $student, 403);

        $hasAccess = $this->progressService->canAccessWord($student, $vocabularyWord);
        abort_unless($hasAccess, 403);

        $progress = $this->progressService->recordProgress($student, $vocabularyWord, (bool) $request->boolean('correct'));

        return response()->json([
            'success' => true,
            'data' => [
                'vocabulary_word_id' => $vocabularyWord->id,
                'mastery_percent' => $progress->mastery_percent,
                'attempts' => $progress->attempts,
                'correct_attempts' => $progress->correct_attempts,
                'last_practiced_at' => $progress->last_practiced_at?->toISOString(),
                'completed' => (int) $progress->mastery_percent >= 100,
                'xp_awarded' => (bool) $progress->getAttribute('xp_awarded'),
            ],
        ]);
    }

    public function reviewQueue(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->role?->name === 'student', 403);

        $student = $user->student;
        abort_if(! $student, 403);

        $rows = StudentWordProgress::query()
            ->where('student_id', $student->id)
            ->where('mastery_percent', '<', 100)
            ->whereHas('vocabularyWord.vocabularyLevel.schoolClasses.students', function ($query) use ($student) {
                $query->where('students.id', $student->id);
            })
            ->with('vocabularyWord.vocabularyLevel')
            ->orderBy('mastery_percent', 'asc')
            ->orderByRaw('last_practiced_at IS NOT NULL ASC')
            ->orderBy('last_practiced_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rows->map(function (StudentWordProgress $row) {
                return [
                    'id' => $row->id,
                    'vocabulary_word_id' => $row->vocabulary_word_id,
                    'word' => $row->vocabularyWord?->word,
                    'translation' => $row->vocabularyWord?->translation,
                    'example' => $row->vocabularyWord?->example,
                    'vocabulary_level' => [
                        'id' => $row->vocabularyWord?->vocabularyLevel?->id,
                        'title' => $row->vocabularyWord?->vocabularyLevel?->title,
                    ],
                    'mastery_percent' => (int) $row->mastery_percent,
                    'attempts' => (int) $row->attempts,
                    'correct_attempts' => (int) $row->correct_attempts,
                    'last_practiced_at' => $row->last_practiced_at?->toISOString(),
                ];
            }),
        ]);
    }

    public function levelReview(Request $request, VocabularyLevel $vocabularyLevel)
    {
        $user = $request->user();
        abort_unless($user && $user->role?->name === 'student', 403);

        $student = $user->student;
        abort_if(! $student, 403);

        $hasAccess = $student->schoolClasses()
            ->whereHas('vocabularyLevels', function ($query) use ($vocabularyLevel) {
                $query->where('vocabulary_levels.id', $vocabularyLevel->id);
            })
            ->exists();

        abort_unless($hasAccess, 403);

        $rows = StudentWordProgress::query()
            ->where('student_id', $student->id)
            ->where('mastery_percent', '<', 100)
            ->whereHas('vocabularyWord', function ($query) use ($vocabularyLevel) {
                $query->where('vocabulary_level_id', $vocabularyLevel->id);
            })
            ->with('vocabularyWord.vocabularyLevel')
            ->orderBy('mastery_percent', 'asc')
            ->orderBy('last_practiced_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rows->map(function (StudentWordProgress $row) {
                return [
                    'id' => $row->id,
                    'vocabulary_word_id' => $row->vocabulary_word_id,
                    'word' => $row->vocabularyWord?->word,
                    'translation' => $row->vocabularyWord?->translation,
                    'example' => $row->vocabularyWord?->example,
                    'mastery_percent' => (int) $row->mastery_percent,
                    'attempts' => (int) $row->attempts,
                    'correct_attempts' => (int) $row->correct_attempts,
                    'last_practiced_at' => $row->last_practiced_at?->toISOString(),
                ];
            }),
        ]);
    }

    public function submitReview(StudentReviewUpdateRequest $request, VocabularyWord $vocabularyWord)
    {
        $user = $request->user();
        abort_unless($user && $user->role?->name === 'student', 403);

        $student = $user->student;
        abort_if(! $student, 403);

        $hasAccess = $this->progressService->canAccessWord($student, $vocabularyWord);
        abort_unless($hasAccess, 403);

        $progress = StudentWordProgress::query()
            ->where('student_id', $student->id)
            ->where('vocabulary_word_id', $vocabularyWord->id)
            ->first();

        abort_if(! $progress, 422, 'No review progress exists for this vocabulary word.');
        abort_if((int) $progress->mastery_percent >= 100, 422, 'This word is already mastered and is not reviewable.');

        $updatedProgress = $this->progressService->recordProgress($student, $vocabularyWord, (bool) $request->boolean('correct'));

        return response()->json([
            'success' => true,
            'data' => [
                'vocabulary_word_id' => $vocabularyWord->id,
                'mastery_percent' => $updatedProgress->mastery_percent,
                'attempts' => $updatedProgress->attempts,
                'correct_attempts' => $updatedProgress->correct_attempts,
                'last_practiced_at' => $updatedProgress->last_practiced_at?->toISOString(),
                'completed' => (int) $updatedProgress->mastery_percent >= 100,
            ],
        ]);
    }
}
