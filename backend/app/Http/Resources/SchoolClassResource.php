<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SchoolClassResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'language' => $this->language,
            'teacher' => $this->whenLoaded('teacher', function () {
                return [
                    'id' => $this->teacher->id,
                    'name' => $this->teacher->user?->name,
                    'email' => $this->teacher->user?->email,
                ];
            }),
            'students_count' => $this->whenCounted('students'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
