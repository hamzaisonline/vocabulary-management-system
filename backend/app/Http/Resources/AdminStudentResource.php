<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminStudentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->user?->name,
            'email' => $this->user?->email,
            'total_xp' => (int) $this->total_xp,
            'enrolled_classes_count' => $this->whenCounted('schoolClasses'),
            'enrolled_classes' => $this->whenLoaded('schoolClasses', fn () => $this->schoolClasses->map(fn ($class) => [
                'id' => $class->id,
                'name' => $class->name,
                'language' => $class->language,
                'status' => $class->pivot?->status,
            ])),
            'created_at' => $this->user?->created_at,
        ];
    }
}
