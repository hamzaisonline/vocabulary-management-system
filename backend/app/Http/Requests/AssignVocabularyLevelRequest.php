<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignVocabularyLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $schoolClass = $this->route('schoolClass');

        if (! $user || ! $schoolClass) {
            return false;
        }

        if ($user->role?->name === 'admin') {
            return true;
        }

        return $user->role?->name === 'teacher' && $user->teacher?->id === $schoolClass->teacher_id;
    }

    public function rules(): array
    {
        return [
            'vocabulary_level_id' => ['required', 'integer', 'exists:vocabulary_levels,id'],
        ];
    }
}
