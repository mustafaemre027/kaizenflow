<?php

namespace App\Http\Requests;

use App\Models\ApprovalWorkflow;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;
use App\Enums\UserCapability;

class MutateApprovalStageApproverRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active && Gate::allows('update', ApprovalWorkflow::class);
    }

    public function rules(): array
    {
        return [
            'capability' => ['required', new Enum(UserCapability::class)],
            'is_active' => ['sometimes', 'boolean'],
            'scope_source' => ['prohibited'],
            'actor_user_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'role' => ['prohibited'],
            'approval_group_id' => ['prohibited'],
            'approval_stage_id' => ['prohibited'],
            'approval_workflow_id' => ['prohibited'],
            'approver_resolution_mode' => ['prohibited'],
            'published_at' => ['prohibited'],
            'is_default' => ['prohibited'],
            'audit' => ['prohibited'],
            'grant' => ['prohibited'],
            'department_id' => ['prohibited'],
        ];
    }
}
