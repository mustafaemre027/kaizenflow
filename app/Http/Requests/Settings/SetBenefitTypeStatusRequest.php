<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class SetBenefitTypeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $benefitType = $this->route('benefitType');

        return $benefitType && $this->user()->can('update', $benefitType);
    }

    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
        ];
    }
}
