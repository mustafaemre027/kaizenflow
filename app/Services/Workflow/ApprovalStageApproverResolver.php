<?php

namespace App\Services\Workflow;

use App\Models\Kaizen;
use App\Models\User;

class ApprovalStageApproverResolver
{
    /**
     * Determines if a user can act on the current stage of a Kaizen.
     */
    public function canAct(User $user, Kaizen $kaizen): bool
    {
        // 1. Fail closed on basic checks
        if (! $user->is_active) {
            return false;
        }

        $instance = $kaizen->workflowInstance;

        // 2. Active workflow instance check
        if (! $instance || $instance->completed_at || $instance->cancelled_at) {
            return false;
        }

        $currentStage = $instance->currentStage;
        if (! $currentStage || ! $currentStage->is_active) {
            return false;
        }

        // 3. User must have at least one active membership in an active group assigned to this stage
        // that satisfies the scope (GLOBAL or DEPARTMENT matching the kaizen's department).
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
