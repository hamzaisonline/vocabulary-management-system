<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentReviewUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role?->name === 'student';
    }

    public function rules(): array
    {
        return [
            'correct' => ['required', 'boolean'],
            'student_id' => ['prohibited'],
            'mastery_percent' => ['prohibited'],
            'xp' => ['prohibited'],
            'completed_at' => ['prohibited'],
        ];
    }
}
