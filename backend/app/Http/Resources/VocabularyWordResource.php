<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VocabularyWordResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'vocabulary_level_id' => $this->vocabulary_level_id,
            'word' => $this->word,
            'translation' => $this->translation,
            'example' => $this->example,
            'notes' => $this->notes,
            'audio_path' => $this->audio_path,
        ];
    }
}
