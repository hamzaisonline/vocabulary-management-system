<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentProgressUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role?->name === 'student';
    }

    public function rules(): array
    {
        return [
            'correct' => ['required', 'boolean'],
        ];
    }
}
