<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVocabularyWordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && in_array($this->user()->role?->name, ['admin', 'teacher'], true);
    }

    public function rules(): array
    {
        return [
            'word' => ['required', 'string', 'max:255'],
            'translation' => ['required', 'string', 'max:255'],
            'example' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'audio_path' => ['nullable', 'string'],
            'audio' => ['nullable', 'file', 'mimes:mp3,wav,m4a,ogg', 'max:10240'],
        ];
    }
}
