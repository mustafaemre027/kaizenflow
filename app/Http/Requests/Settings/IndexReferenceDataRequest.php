<?php

namespace App\Http\Requests\Settings;

use App\Models\BenefitType;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexReferenceDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Category::class)
            || $this->user()->can('viewAny', BenefitType::class);
    }

    public function rules(): array
    {
        return [
            'category_q' => ['nullable', 'string', 'max:100'],
            'department_q' => ['nullable', 'string', 'max:100'],
            'benefit_type_q' => ['nullable', 'string', 'max:100'],
            'category_status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'department_status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'benefit_type_status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'category_page' => ['nullable', 'integer', 'min:1'],
            'department_page' => ['nullable', 'integer', 'min:1'],
            'benefit_type_page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
