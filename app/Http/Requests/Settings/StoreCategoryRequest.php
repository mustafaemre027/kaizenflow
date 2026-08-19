<?php

namespace App\Http\Requests\Settings;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Category::class);
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
            'name' => ['required', 'string', 'max:150', 'unique:categories,name'],
            'code' => ['required', 'string', 'max:30', 'unique:categories,code', 'regex:/^[A-Z0-9_\-]+$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Kategori Adı',
            'code' => 'Kategori Kodu',
            'description' => 'Açıklama',
        ];
    }
}
