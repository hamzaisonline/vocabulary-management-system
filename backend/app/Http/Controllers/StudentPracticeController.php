<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePracticeAttemptRequest;
use App\Http\Resources\PracticeAttemptResource;
use App\Http\Resources\PracticeSessionResource;
use App\Models\PracticeAttempt;
use App\Models\PracticeSession;
use App\Models\Student;
use App\Models\VocabularyLevel;
use App\Models\VocabularyWord;
use App\Services\PracticeSessionService;
use App\Services\StudentProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentPracticeController extends Controller
{
    public function __construct(
        protected PracticeSessionService $practiceSessionService,
        protected StudentProgressService $progressService,
    ) {
    }

    public function start(Request $request, VocabularyLevel $vocabularyLevel)
    {
        $user = $request->user();
        abort_unless($user && $user->role?->name === 'student', 403);

        $student = $user->student;
        abort_if(! $student, 403);

        abort_unless($this->practiceSessionService->canAccessLevel($student, $vocabularyLevel), 403);

        $session = PracticeSession::create([
            'student_id' => $student->id,
            'vocabulary_level_id' => $vocabularyLevel->id,
            'started_at' => now(),
            'total_questions' => 0,
            'correct_answers' => 0,
            'score_percent' => 0,
        ]);

        $session->load('vocabularyLevel');

        return response()->json([
            'success' => true,
            'data' => [
                'session' => new PracticeSessionResource($session),
                'level' => [
                    'id' => $vocabularyLevel->id,
                    'title' => $vocabularyLevel->title,
                    'description' => $vocabularyLevel->description,
                    'difficulty' => $vocabularyLevel->difficulty,
                ],
                'questions' => $this->practiceSessionService->buildPracticeQuestions($vocabularyLevel)->values()->all(),
            ],
        ], 201);
    }

    public function storeAttempt(StorePracticeAttemptRequest $request, PracticeSession $practiceSession)
    {
        $user = $request->user();
        abort_unless($user && $user->role?->name === 'student', 403);

        $student = $user->student;
        abort_if(! $student, 403);

        abort_unless($practiceSession->student_id === $student->id, 403);
        abort_if($practiceSession->completed_at !== null, 422, 'This practice session has already been completed.');

        $wordId = $request->validated('vocabulary_word_id');
        $word = VocabularyWord::whereKey($wordId)->firstOrFail();

        abort_unless($word->vocabulary_level_id === $practiceSession->vocabulary_level_id, 422, 'This word does not belong to the session level.');

        $alreadyAttempted = $practiceSession->practiceAttempts()->where('vocabulary_word_id', $wordId)->exists();
        if ($alreadyAttempted) {
            return response()->json([
                'success' => false,
                'message' => 'This word has already been attempted in this session.',
            ], 409);
        }

        $submittedAnswer = (string) $request->input('submitted_answer');
        $isCorrect = $this->practiceSessionService->isCorrectAnswer($word, $submittedAnswer);

        $result = DB::transaction(function () use ($practiceSession, $word, $submittedAnswer, $isCorrect, $student) {
            $attempt = $practiceSession->practiceAttempts()->create([
                'vocabulary_word_id' => $word->id,
                'submitted_answer' => $submittedAnswer,
                'is_correct' => $isCorrect,
                'attempted_at' => now(),
            ]);

            $this->progressService->recordProgress($student, $word, $isCorrect);

            return $attempt->fresh();
        });

        return response()->json([
            'success' => true,
            'data' => new PracticeAttemptResource($result),
            'message' => $isCorrect ? 'Correct answer submitted.' : 'Incorrect answer submitted.',
        ], 201);
    }

    public function complete(Request $request, PracticeSession $practiceSession)
    {
        $user = $request->user();
        abort_unless($user && $user->role?->name === 'student', 403);

        $student = $user->student;
        abort_if(! $student, 403);

        abort_unless($practiceSession->student_id === $student->id, 403);

        if ($practiceSession->completed_at !== null) {
            return response()->json([
                'success' => true,
                'data' => new PracticeSessionResource($practiceSession->fresh()->load('vocabularyLevel')),
                'message' => 'Practice session already completed.',
            ]);
        }

        $session = $this->practiceSessionService->completeSession($practiceSession);

        return response()->json([
            'success' => true,
            'data' => new PracticeSessionResource($session->load('vocabularyLevel')),
            'message' => 'Practice session completed.',
        ]);
    }

    public function show(Request $request, PracticeSession $practiceSession)
    {
        $user = $request->user();
        abort_unless($user && $user->role?->name === 'student', 403);

        $student = $user->student;
        abort_if(! $student, 403);

        abort_unless($practiceSession->student_id === $student->id, 403);

        $practiceSession->load(['vocabularyLevel', 'practiceAttempts.vocabularyWord']);

        return response()->json([
            'success' => true,
            'data' => new PracticeSessionResource($practiceSession),
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->role?->name === 'student', 403);

        $student = $user->student;
        abort_if(! $student, 403);

        $sessions = PracticeSession::with('vocabularyLevel')
            ->where('student_id', $student->id)
            ->orderByDesc('started_at')
            ->get();

        $payload = $sessions->map(function (PracticeSession $session) {
            return [
                'id' => $session->id,
                'level' => [
                    'id' => $session->vocabularyLevel?->id,
                    'title' => $session->vocabularyLevel?->title,
                ],
                'started_at' => $session->started_at?->toISOString(),
                'completed_at' => $session->completed_at?->toISOString(),
                'score_percent' => (int) $session->score_percent,
                'total_questions' => (int) $session->total_questions,
                'correct_answers' => (int) $session->correct_answers,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }
}
