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
            'benefits.*.benefit_type_id' => ['nullable', 'integer'],
            'benefits.*.realized_value' => ['nullable', 'string', 'max:50'],
            'benefits.*.realized_note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
