<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentWordProgress;
use App\Models\VocabularyWord;
use Illuminate\Support\Facades\DB;

class StudentProgressService
{
    public function recordProgress(Student $student, VocabularyWord $vocabularyWord, bool $correct): StudentWordProgress
    {
        return DB::transaction(function () use ($student, $vocabularyWord, $correct) {
            $xpAwarded = false;
            $progress = StudentWordProgress::firstOrNew([
                'student_id' => $student->id,
                'vocabulary_word_id' => $vocabularyWord->id,
            ]);

            $previousMastery = (int) $progress->mastery_percent;

            $progress->attempts = (int) $progress->attempts + 1;
            $progress->last_practiced_at = now();

            if ($correct) {
                $progress->correct_attempts = (int) $progress->correct_attempts + 1;

                $newMastery = min(100, $previousMastery + 25);
                $canAwardXp = $newMastery > $previousMastery;

                if ($canAwardXp) {
                    $progress->mastery_percent = $newMastery;

                    if ($previousMastery < 100 && $newMastery >= 100) {
                        $progress->completed_at = now();
                    }

                    $student->total_xp = (int) $student->total_xp + 10;
                    $student->save();
                    $xpAwarded = true;
                } else {
                    $progress->mastery_percent = 100;
                    if ($previousMastery < 100 && $progress->completed_at === null) {
                        $progress->completed_at = now();
                    }
                }
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
