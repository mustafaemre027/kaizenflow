<?php

namespace App\Http\Requests;

use App\Models\ApprovalWorkflow;
use Illuminate\Foundation\Http\FormRequest;

class UpdateApprovalWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', ApprovalWorkflow::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'stages' => ['required', 'array', 'min:1', 'max:50'],
            'stages.*.id' => ['nullable', 'integer', 'min:1'],
            'stages.*.code' => ['required', 'string', 'max:255'],
            'stages.*.name' => ['required', 'string', 'max:255'],
            'stages.*.description' => ['nullable', 'string', 'max:1000'],
            'stages.*.sequence' => ['required', 'integer', 'min:1'],
            'stages.*.is_final' => ['nullable', 'boolean'],
            'stages.*.is_active' => ['nullable', 'boolean'],

            // Prohibited system fields
            'id' => 'prohibited',
            'version' => 'prohibited',
            'published_at' => 'prohibited',
            'is_active' => 'prohibited',
            'is_default' => 'prohibited',
            'created_at' => 'prohibited',
            'updated_at' => 'prohibited',
            'actor_user_id' => 'prohibited',
            'user_id' => 'prohibited',
            'granted_by_user_id' => 'prohibited',
            'audit' => 'prohibited',
            'metadata' => 'prohibited',
            'role' => 'prohibited',
            'capability' => 'prohibited',
        ];
    }
}
