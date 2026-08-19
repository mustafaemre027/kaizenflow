<?php

namespace App\Http\Requests\Settings;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexReferenceDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Category::class);
    }

    public function rules(): array
    {
        return [
            'category_q' => ['nullable', 'string', 'max:100'],
            'department_q' => ['nullable', 'string', 'max:100'],
            'category_status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'department_status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'category_page' => ['nullable', 'integer', 'min:1'],
            'department_page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
