<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVocabularyLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && in_array($this->user()->role?->name, ['admin', 'teacher'], true);
    }

    public function rules(): array
    {
        $levelId = $this->route('vocabularyLevel')?->id;

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255', 'unique:vocabulary_levels,title,' . $levelId],
            'description' => ['nullable', 'string'],
            'difficulty' => ['nullable', 'string', 'max:100'],
            'stage' => ['nullable', 'string', 'max:100'],
            'visibility' => ['sometimes', 'string', 'in:private,shared'],
        ];
    }
}
