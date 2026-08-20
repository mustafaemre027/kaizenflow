<?php

namespace App\Policies;

use App\Enums\KaizenStatus;
use App\Enums\UserRole;
use App\Models\Kaizen;
use App\Models\User;
use App\Services\Workflow\ApprovalStageApproverResolver;

class KaizenPolicy
{
    private function hasCanonicalRole(User $user): bool
    {
        return in_array($user->role, [
            UserRole::EMPLOYEE,
            UserRole::OPEX_SPECIALIST,
            UserRole::MANAGER,
            UserRole::ADMIN,
        ], true);
    }

    public function viewAny(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $this->hasCanonicalRole($user);
    }

    public function view(User $user, Kaizen $kaizen): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($kaizen->creator_user_id === $user->id) {
            return true;
        }

        if ($kaizen->assigned_user_id === $user->id) {
            return true;
        }

        if ($user->role === UserRole::OPEX_SPECIALIST) {
            return true;
        }

        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        if ($user->role === UserRole::MANAGER && $user->department_id === $kaizen->department_id) {
            return true;
        }

        // Eligible current stage approvers can view the Kaizen
        if (app(ApprovalStageApproverResolver::class)->isAssigned($user, $kaizen)) {
            return true;
        }

        // Past actors (reviewers who made a decision on this Kaizen) retain view access
        // for the history archive. This does not grant any write/action rights.
        if ($kaizen->workflowTransitions()->where('actor_user_id', $user->id)->exists()) {
            return true;
        }

        return false;
    }

    public function reviewOnWorkflow(User $user, Kaizen $kaizen): bool
    {
        return app(ApprovalStageApproverResolver::class)->canAct($user, $kaizen);
    }

    public function create(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if (! $user->department_id) {
            return false;
        }

        if (! $user->department || ! $user->department->is_active) {
            return false;
        }

        return $this->hasCanonicalRole($user);
    }

    public function update(User $user, Kaizen $kaizen): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($kaizen->creator_user_id !== $user->id) {
            return false;
        }

        return in_array($kaizen->status, [
            KaizenStatus::DRAFT,
            KaizenStatus::REVISION_REQUESTED,
        ], true);
    }

    public function submit(User $user, Kaizen $kaizen): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($kaizen->creator_user_id !== $user->id) {
            return false;
        }

        if (! in_array($kaizen->status, [KaizenStatus::DRAFT, KaizenStatus::REVISION_REQUESTED], true)) {
            return false;
        }

        return $user->role === UserRole::EMPLOYEE;
    }

    public function delete(User $user, Kaizen $kaizen): bool
    {
        return false;
    }

    public function restore(User $user, Kaizen $kaizen): bool
    {
        return false;
    }

    public function forceDelete(User $user, Kaizen $kaizen): bool
    {
        return false;
    }
}
