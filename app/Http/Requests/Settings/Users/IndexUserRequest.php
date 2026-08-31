<?php

namespace App\Http\Requests\Settings\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', User::class);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'setup' => ['nullable', 'string', Rule::in(['pending', 'ready'])],
            'sort' => ['nullable', 'string', Rule::in(['name', 'created_at'])],
            'direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ];
    }
}
