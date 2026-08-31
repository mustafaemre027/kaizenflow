<?php

namespace App\Http\Requests\Settings\Users;

use App\Enums\CapabilityScope;
use App\Enums\UserCapability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetSystemCapabilityRequest extends FormRequest
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
                    if ($capability && $capability->scope() !== CapabilityScope::SYSTEM) {
                        $fail('Sadece sistem yetkileri yönetilebilir.');
                    }
                },
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
