<?php

namespace App\Http\Requests;

use App\Enums\KaizenStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class DashboardIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // We use controller-level capability check
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['nullable', new Enum(KaizenStatus::class)],
        ];
    }
}
