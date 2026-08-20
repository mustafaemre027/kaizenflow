<?php

namespace App\Http\Requests\Kaizens;

use Illuminate\Foundation\Http\FormRequest;

class AssignKaizenImplementationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assigned_user_id' => ['required', 'integer', 'exists:users,id'],
            'target_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }
}
