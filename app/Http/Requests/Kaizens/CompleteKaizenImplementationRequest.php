<?php

namespace App\Http\Requests\Kaizens;

use Illuminate\Foundation\Http\FormRequest;

class CompleteKaizenImplementationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actual_result' => ['required', 'string', 'max:5000', function ($attribute, $value, $fail) {
                if (trim($value) === '') {
                    $fail('The actual result cannot be empty.');
                }
            }],
        ];
    }
}
