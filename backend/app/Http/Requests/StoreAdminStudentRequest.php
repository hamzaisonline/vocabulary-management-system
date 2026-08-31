<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdminStudentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->role?->name === 'admin'; }
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['prohibited'],
            'role' => ['prohibited'],
            'student_id' => ['prohibited'],
        ];
    }
}
