<?php

namespace App\Http\Requests\Kaizens;

use Illuminate\Foundation\Http\FormRequest;

class CompleteKaizenImplementationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $kaizen = $this->route('kaizen');

        return $kaizen && $this->user()->can('completeImplementation', $kaizen);
    }

    public function rules(): array
    {
        return [
            'actual_result' => ['required', 'string', 'max:5000', function ($attribute, $value, $fail) {
                if (trim($value) === '') {
                    $fail('Gerçekleşen sonuç alanı boş bırakılamaz.');
                }
            }],
            'benefits' => ['nullable', 'array'],
            'benefits.*.benefit_type_id' => [
                'nullable',
                'integer',
                'distinct',
                'required_with:benefits.*.realized_value,benefits.*.realized_note',
            ],
            'benefits.*.realized_value' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999999.9999',
                'regex:/^\d{1,11}(\.\d{1,4})?$/',
            ],
            'benefits.*.realized_note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
