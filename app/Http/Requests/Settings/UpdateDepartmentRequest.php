<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $department = $this->route('department');

        return $this->user()->can('update', $department);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => trim($this->name)]);
        }
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim($this->code))]);
        }
    }

    public function rules(): array
    {
        $departmentId = $this->route('department')->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:150', Rule::unique('departments', 'name')->ignore($departmentId)],
            'code' => ['sometimes', 'required', 'string', 'max:30', Rule::unique('departments', 'code')->ignore($departmentId), 'regex:/^[A-Z0-9_\-]+$/'],
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
