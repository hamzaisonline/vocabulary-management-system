<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->whenLoaded('role', function () {
                return [
                    'id' => $this->role->id,
                    'name' => $this->role->name,
                    'description' => $this->role->description,
                ];
            }),
        ];

        if ($this->relationLoaded('student') && $this->student) {
            $data['student'] = [
                'id' => $this->student->id,
                'user_id' => $this->student->user_id,
            ];
        }

        if ($this->relationLoaded('teacher') && $this->teacher) {
            $data['teacher'] = [
                'id' => $this->teacher->id,
                'user_id' => $this->teacher->user_id,
            ];
        }

        return $data;
    }
}
