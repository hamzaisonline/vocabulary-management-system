<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PracticeSessionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'vocabulary_level_id' => $this->vocabulary_level_id,
            'level' => $this->whenLoaded('vocabularyLevel', function () {
                return [
                    'id' => $this->vocabularyLevel->id,
                    'title' => $this->vocabularyLevel->title,
                    'description' => $this->vocabularyLevel->description,
                    'difficulty' => $this->vocabularyLevel->difficulty,
                ];
            }),
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'total_questions' => (int) $this->total_questions,
            'correct_answers' => (int) $this->correct_answers,
            'score_percent' => (int) $this->score_percent,
            'is_completed' => (bool) $this->completed_at,
            'attempts' => PracticeAttemptResource::collection($this->whenLoaded('practiceAttempts')),
        ];
    }
}
