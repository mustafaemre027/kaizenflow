<?php

namespace App\Services\Workflow;

use App\Enums\KaizenStatus;
use App\Models\Kaizen;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PendingApprovalsQuery
{
    public function forUser(User $user): Builder
    {
        // Fail closed for inactive users
        if (! $user->is_active) {
            return Kaizen::query()->whereRaw('1 = 0');
        }

        return Kaizen::query()
            ->where('status', KaizenStatus::SUBMITTED->value)
            ->whereHas('workflowInstance', function (Builder $instanceQuery) use ($user) {
                $instanceQuery->whereNull('completed_at')
                    ->whereNull('cancelled_at')
                    ->whereHas('currentStage', function (Builder $stageQuery) use ($user) {
                        $stageQuery->where('is_active', true)
                            ->whereHas('stageAssignments', function (Builder $assignmentQuery) use ($user) {
                                $assignmentQuery->where('is_active', true)
                                    ->whereHas('group', function (Builder $groupQuery) use ($user) {
                                        $groupQuery->where('is_active', true)
                                            ->whereHas('members', function (Builder $memberQuery) use ($user) {
                                                $memberQuery->where('user_id', $user->id)
                                                    ->where('is_active', true);
                                            });
                                    })
                                    ->where(function (Builder $scopeQuery) {
                                        $scopeQuery->where('scope', 'GLOBAL')
                                            ->orWhere(function (Builder $deptQuery) {
                                                $deptQuery->where('scope', 'DEPARTMENT')
                                                    ->whereColumn('approval_stage_assignments.department_id', 'kaizens.department_id');
                                            });
                                    });
                            });
                    });
            });
    }
}
