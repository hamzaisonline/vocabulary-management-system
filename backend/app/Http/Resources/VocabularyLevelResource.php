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
            'stage' => $this->stage,
            'visibility' => $this->visibility,
            'created_by_user_id' => $this->created_by_user_id,
            'is_owner' => $request->user()?->id === $this->created_by_user_id,
            'owner' => $this->whenLoaded('owner', fn () => $this->owner ? [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
            ] : null),
            'word_count' => $this->when(isset($this->words_count), (int) $this->words_count, $this->words->count()),
            'words' => VocabularyWordResource::collection($this->whenLoaded('words')),
        ];
    }
}
