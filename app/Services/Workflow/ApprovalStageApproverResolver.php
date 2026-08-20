<?php

namespace App\Services\Workflow;

use App\Enums\KaizenStatus;
use App\Models\Kaizen;
use App\Models\User;

class ApprovalStageApproverResolver
{
    /**
     * Determines if a user can act on the current stage of a Kaizen.
     */
    public function canAct(User $user, Kaizen $kaizen): bool
    {
        // 1. Canonical Eligibility Contract
        if ($kaizen->status !== KaizenStatus::SUBMITTED) {
            return false;
        }

        return $this->isAssigned($user, $kaizen);
    }

    /**
     * Determines if a user is currently assigned to the active stage of a Kaizen.
     * Does not check the Kaizen's lifecycle status.
     */
    public function isAssigned(User $user, Kaizen $kaizen): bool
    {
        if (! $user->is_active) {
            return false;
        }

        $instance = $kaizen->workflowInstance;

        if (! $instance || $instance->completed_at || $instance->cancelled_at) {
            return false;
        }

        $currentStage = $instance->currentStage;
        if (! $currentStage || ! $currentStage->is_active) {
            return false;
        }

        $eligibleAssignmentsCount = $currentStage->stageAssignments()
            ->where('is_active', true)
            ->whereHas('group', function ($groupQuery) use ($user) {
                $groupQuery->where('is_active', true)
                    ->whereHas('members', function ($memberQuery) use ($user) {
                        $memberQuery->where('user_id', $user->id)
                            ->where('is_active', true);
                    });
            })
            ->where(function ($scopeQuery) use ($kaizen) {
                $scopeQuery->where('scope', 'GLOBAL')
                    ->orWhere(function ($deptQuery) use ($kaizen) {
                        $deptQuery->where('scope', 'DEPARTMENT')
                            ->where('department_id', $kaizen->department_id);
                    });
            })
            ->count();

        return $eligibleAssignmentsCount > 0;
    }
}
