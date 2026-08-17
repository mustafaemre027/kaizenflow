<?php

namespace App\Policies;

use App\Enums\KaizenStatus;
use App\Enums\UserRole;
use App\Models\Kaizen;
use App\Models\User;

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

        return false;
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

        return in_array($kaizen->status, [
            KaizenStatus::DRAFT,
            KaizenStatus::REVISION_REQUESTED,
        ], true);
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
