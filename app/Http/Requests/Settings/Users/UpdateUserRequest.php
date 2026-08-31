<?php

namespace App\Http\Requests\Settings\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // handled by controller
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim($this->email)),
            ]);
        }
    }

    public function rules(): array
    {
        $role = $this->input('role');
        $departmentRequired = in_array($role, [UserRole::EMPLOYEE->value, UserRole::MANAGER->value]);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->route('user')->id),
            ],
            'department_id' => [
                $departmentRequired ? 'required' : 'nullable',
                Rule::exists('departments', 'id')->where('is_active', true),
            ],
            'role' => ['required', new Enum(UserRole::class)],

            // Prohibited server-controlled fields
            'password' => ['prohibited'],
            'password_confirmation' => ['prohibited'],
            'must_set_password' => ['prohibited'],
            'invitation_sent_at' => ['prohibited'],
            'email_verified_at' => ['prohibited'],
            'is_active' => ['prohibited'],
            'remember_token' => ['prohibited'],
            'capability' => ['prohibited'],
            'capabilities' => ['prohibited'],
            'system_capabilities' => ['prohibited'],
            'department_capabilities' => ['prohibited'],
        ];
    }
}
