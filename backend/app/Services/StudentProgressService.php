<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentWordProgress;
use App\Models\VocabularyWord;
use Illuminate\Support\Facades\DB;

class StudentProgressService
{
    public const REVIEW_THRESHOLD = 90;

    public function effectiveMastery(?StudentWordProgress $progress): int
    {
        if (! $progress) {
            return 0;
        }

        $storedMastery = max(0, min(100, (int) $progress->mastery_percent));
        $lastPracticedAt = $progress->last_practiced_at;

        if (! $lastPracticedAt || $lastPracticedAt->isFuture()) {
            return $storedMastery;
        }

        $fullDays = intdiv($lastPracticedAt->diffInSeconds(now()), 86400);

        return max(0, min($storedMastery, $storedMastery - ($fullDays * 2)));
    }

    public function isReviewEligible(?StudentWordProgress $progress): bool
    {
        return $progress !== null && $this->effectiveMastery($progress) < self::REVIEW_THRESHOLD;
    }

    public function recordProgress(Student $student, VocabularyWord $vocabularyWord, bool $correct): StudentWordProgress
    {
        return DB::transaction(function () use ($student, $vocabularyWord, $correct) {
            $xpAwarded = false;
            $progress = StudentWordProgress::firstOrNew([
                'student_id' => $student->id,
                'vocabulary_word_id' => $vocabularyWord->id,
            ]);

            $previousMastery = (int) $progress->mastery_percent;
            $effectiveMastery = $progress->exists ? $this->effectiveMastery($progress) : $previousMastery;
            $wasPreviouslyCompleted = $progress->completed_at !== null;

            $progress->attempts = (int) $progress->attempts + 1;
            $progress->last_practiced_at = now();

            if ($correct) {
                $progress->correct_attempts = (int) $progress->correct_attempts + 1;

                $newMastery = min(100, $effectiveMastery + 25);
                $masteryIncreased = $newMastery > $effectiveMastery;
                $canAwardXp = $masteryIncreased && ! $wasPreviouslyCompleted;

                if ($masteryIncreased) {
                    $progress->mastery_percent = $newMastery;

                    if (! $wasPreviouslyCompleted && $newMastery >= 100) {
                        $progress->completed_at = now();
                    }

                    if ($canAwardXp) {
                        $student->total_xp = (int) $student->total_xp + 10;
                        $student->save();
                        $xpAwarded = true;
                    }
                } else {
                    $progress->mastery_percent = $previousMastery;
                    if ($previousMastery >= 100 && $progress->completed_at === null) {
                        $progress->completed_at = now();
                    }
                }
            } else {
                // Materialize the current decayed value before resetting the practice timestamp.
                // This prevents an incorrect attempt from implicitly restoring historical mastery.
                $progress->mastery_percent = $effectiveMastery;
            }

            if (! $correct && $progress->completed_at === null && (int) $progress->mastery_percent >= 100) {
                $progress->completed_at = now();
            }

            $progress->save();

            $freshProgress = $progress->fresh();
            $freshProgress->setAttribute('xp_awarded', $xpAwarded);

            return $freshProgress;
        });
    }

    public function canAccessWord(Student $student, VocabularyWord $word): bool
    {
        return $student->schoolClasses()
            ->whereHas('vocabularyLevels.words', function ($query) use ($word) {
                $query->where('vocabulary_words.id', $word->id);
            })
            ->exists();
    }
}
