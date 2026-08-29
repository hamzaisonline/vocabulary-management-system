<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && in_array($this->user()->role?->name, ['admin', 'teacher'], true);
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'teacher_id' => ['sometimes', 'nullable', 'integer', 'exists:teachers,id'],
            'description' => ['nullable', 'string'],
            'language' => ['nullable', 'string', 'max:50'],
        ];
    }
}
