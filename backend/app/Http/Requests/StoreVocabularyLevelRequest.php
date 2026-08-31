<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVocabularyLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && in_array($this->user()->role?->name, ['admin', 'teacher'], true);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'unique:vocabulary_levels,title'],
            'description' => ['nullable', 'string'],
            'difficulty' => ['nullable', 'string', 'max:100'],
            'stage' => ['nullable', 'string', 'max:100'],
            'visibility' => ['sometimes', 'string', 'in:private,shared'],
        ];
    }
}
