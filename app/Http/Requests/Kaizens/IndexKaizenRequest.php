<?php

namespace App\Http\Requests\Kaizens;

use App\Enums\KaizenStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class IndexKaizenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // We use controller viewAny policy
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', new Enum(KaizenStatus::class)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'sort' => ['nullable', 'string', 'in:created_at,updated_at,code,title,status,target_date'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
