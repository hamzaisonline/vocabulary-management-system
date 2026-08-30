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
            'audio_url' => $this->audioUrl($request),
        ];
    }

    private function audioUrl($request): ?string
    {
        if (!$this->audio_path) {
            return null;
        }

        if (filter_var($this->audio_path, FILTER_VALIDATE_URL)) {
            return $this->audio_path;
        }

        if (str_starts_with($this->audio_path, '/')) {
            return url($this->audio_path);
        }

        return rtrim($request->getSchemeAndHttpHost(), '/')
            . '/storage/'
            . ltrim($this->audio_path, '/');
    }
}
