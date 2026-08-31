<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminStudentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->role?->name === 'admin'; }
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('student')->user_id)],
            'role_id' => ['prohibited'], 'role' => ['prohibited'], 'student_id' => ['prohibited'],
            'password' => ['prohibited'], 'total_xp' => ['prohibited'], 'mastery_percent' => ['prohibited'],
        ];
    }
}
