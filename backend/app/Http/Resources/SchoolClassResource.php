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
            'students_count' => $this->when(
                isset($this->students_count) || $this->relationLoaded('students'),
                fn () => isset($this->students_count)
                    ? (int) $this->students_count
                    : $this->students->count()
            ),
            'students' => $this->whenLoaded('students', function () {
                return $this->students->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'user_id' => $student->user_id,
                        'name' => $student->user?->name,
                        'email' => $student->user?->email,
                    ];
                });
            }),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
