<?php

namespace App\Services\Workflow;

use App\Enums\ApprovalApproverScopeSource;
use App\Enums\ApproverResolutionMode;
use App\Enums\CapabilityScope;
use App\Models\ApprovalStage;
use App\Models\Kaizen;
use App\Models\User;
use App\Services\UserCapabilityResolver;

class CapabilityApprovalStageApproverResolver
{
    public function __construct(
        private readonly UserCapabilityResolver $capabilityResolver
    ) {}

    public function canAct(User $actor, Kaizen $kaizen, ApprovalStage $stage): bool
    {
        if (! $actor->is_active) {
            return false;
        }

        if ($actor->id === $kaizen->creator_user_id) {
            return false; // Prevent self-approval
        }

        if (! $stage->is_active) {
            return false;
        }

        $workflow = $stage->workflow;
        if (! $workflow || $workflow->approver_resolution_mode !== ApproverResolutionMode::CAPABILITY_RULE) {
            return false;
        }

        $rule = $stage->approverRule;
        if (! $rule || ! $rule->is_active) {
            return false;
        }

        $capability = $rule->capability;

        if ($capability->scope() === CapabilityScope::SYSTEM && $rule->scope_source === ApprovalApproverScopeSource::SYSTEM) {
            return $this->capabilityResolver->allowsSystem($actor, $capability);
        }

        if ($capability->scope() === CapabilityScope::DEPARTMENT && $rule->scope_source === ApprovalApproverScopeSource::KAIZEN_DEPARTMENT) {
            return $this->capabilityResolver->allows($actor, $capability, $kaizen->department_id);
        }

        return false;
    }
}
