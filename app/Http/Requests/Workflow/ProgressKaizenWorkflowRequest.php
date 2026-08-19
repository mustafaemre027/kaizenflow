<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class ProgressKaizenWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization is handled by the Controller via Gate
    }

    public function rules(): array
    {
        return [
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'comment.max' => 'Açıklama 2000 karakterden uzun olamaz.',
            'comment.string' => 'Açıklama geçerli bir metin olmalıdır.',
        ];
    }
}
