<?php

namespace App\Services;

use App\Models\PracticeSession;
use App\Models\Student;
use App\Models\VocabularyLevel;
use App\Models\VocabularyWord;
use Illuminate\Support\Collection;

class PracticeSessionService
{
    public function canAccessLevel(Student $student, VocabularyLevel $vocabularyLevel): bool
    {
        return $student->schoolClasses()
            ->whereHas('vocabularyLevels', function ($query) use ($vocabularyLevel) {
                $query->where('vocabulary_levels.id', $vocabularyLevel->id);
            })
            ->exists();
    }

    public function buildPracticeQuestions(VocabularyLevel $vocabularyLevel): Collection
    {
        $words = $vocabularyLevel->words()->get();

        return $words->map(function (VocabularyWord $word) use ($vocabularyLevel) {
            $pool = $vocabularyLevel->words()
                ->whereKeyNot($word->id)
                ->pluck('translation')
                ->filter()
                ->unique()
                ->values();

            $options = collect([$word->translation])
                ->merge($pool->shuffle()->take(min(3, $pool->count())))
                ->unique()
                ->shuffle()
                ->values();

            return [
                'vocabulary_word_id' => $word->id,
                'question' => 'What does the word "' . $word->word . '" mean?',
                'prompt' => $word->word,
                'options' => $options->all(),
                'example' => $word->example,
            ];
        });
    }

    public function isCorrectAnswer(VocabularyWord $word, string $submittedAnswer): bool
    {
        return strtolower(trim($submittedAnswer)) === strtolower(trim((string) $word->translation));
    }

    public function completeSession(PracticeSession $practiceSession): PracticeSession
    {
        $attempts = $practiceSession->practiceAttempts()->get();
        $totalQuestions = $attempts->count();
        $correctAnswers = $attempts->where('is_correct', true)->count();
        $scorePercent = $totalQuestions > 0 ? (int) round(($correctAnswers / $totalQuestions) * 100) : 0;

        $practiceSession->update([
            'completed_at' => now(),
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'score_percent' => $scorePercent,
        ]);

        return $practiceSession->fresh();
    }
}
