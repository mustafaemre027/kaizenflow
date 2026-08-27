<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EmailVerificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/'],
            'user_id' => ['prohibited'],
            'actor_user_id' => ['prohibited'],
            'email' => ['prohibited'],
            'role' => ['prohibited'],
            'is_active' => ['prohibited'],
            'email_verified_at' => ['prohibited'],
        ];
    }
}
