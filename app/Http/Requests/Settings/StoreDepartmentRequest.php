<?php

namespace App\Http\Requests\Settings;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Department::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim($this->name),
            'code' => strtoupper(trim($this->code)),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', 'unique:departments,name'],
            'code' => ['required', 'string', 'max:30', 'unique:departments,code', 'regex:/^[A-Z0-9_\-]+$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Departman Adı',
            'code' => 'Departman Kodu',
            'description' => 'Açıklama',
        ];
    }
}
