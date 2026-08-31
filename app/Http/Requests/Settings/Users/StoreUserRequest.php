<?php

namespace App\Http\Requests\Settings\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email') && is_string($this->email)) {
            $this->merge([
                'email' => strtolower(trim($this->email)),
            ]);
        }

        if ($this->has('name') && is_string($this->name)) {
            $this->merge([
                'name' => trim($this->name),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where(function ($query) {
                    return $query->where('is_active', true);
                }),
            ],

            // Prohibited server-controlled fields
            'password' => ['prohibited'],
            'password_confirmation' => ['prohibited'],
            'must_set_password' => ['prohibited'],
            'invitation_sent_at' => ['prohibited'],
            'email_verified_at' => ['prohibited'],
            'is_active' => ['prohibited'],
            'remember_token' => ['prohibited'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->has('role') || $validator->errors()->has('role')) {
                return; // skip if base rule failed
            }

            $role = UserRole::from($this->role);
            $departmentId = $this->department_id;

            if (in_array($role, [UserRole::EMPLOYEE, UserRole::MANAGER]) && empty($departmentId)) {
                $validator->errors()->add(
                    'department_id',
                    "Departman seçimi {$role->label()} rolü için zorunludur."
                );
            }
        });
    }
}
