<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PracticeAttemptResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'practice_session_id' => $this->practice_session_id,
            'vocabulary_word_id' => $this->vocabulary_word_id,
            'submitted_answer' => $this->submitted_answer,
            'is_correct' => (bool) $this->is_correct,
            'attempted_at' => $this->attempted_at?->toISOString(),
        ];
    }
}
