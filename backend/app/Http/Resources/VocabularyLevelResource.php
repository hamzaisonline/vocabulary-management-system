<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VocabularyLevelResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'difficulty' => $this->difficulty,
            'word_count' => $this->when(isset($this->words_count), (int) $this->words_count, $this->words->count()),
            'words' => VocabularyWordResource::collection($this->whenLoaded('words')),
        ];
    }
}
