<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetAdminStudentPasswordRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->role?->name === 'admin'; }
    public function rules(): array { return ['password' => ['required', 'string', 'min:8']]; }
}
