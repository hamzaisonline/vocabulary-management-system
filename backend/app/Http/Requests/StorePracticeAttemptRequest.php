<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePracticeAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role?->name === 'student';
    }

    public function rules(): array
    {
        return [
            'vocabulary_word_id' => ['required', 'integer', 'exists:vocabulary_words,id'],
            'submitted_answer' => ['required', 'string', 'max:255'],
            'student_id' => ['prohibited'],
            'is_correct' => ['prohibited'],
            'mastery_percent' => ['prohibited'],
            'xp' => ['prohibited'],
        ];
    }
}
