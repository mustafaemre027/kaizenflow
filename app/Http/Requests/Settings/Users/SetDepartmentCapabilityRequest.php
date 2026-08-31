<?php

namespace App\Http\Requests\Settings\Users;

use App\Enums\CapabilityScope;
use App\Enums\UserCapability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetDepartmentCapabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageCapabilities', $this->route('user'));
    }

    public function rules(): array
    {
        return [
            'capability' => [
                'required',
                'string',
                Rule::enum(UserCapability::class),
                function ($attribute, $value, $fail) {
                    $capability = UserCapability::tryFrom($value);
                    if ($capability && $capability->scope() !== CapabilityScope::DEPARTMENT) {
                        $fail('Sadece departman yetkileri yönetilebilir.');
                    }
                },
            ],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
