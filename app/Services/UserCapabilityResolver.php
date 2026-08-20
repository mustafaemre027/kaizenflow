<?php

namespace App\Services;

use App\Enums\UserCapability;
use App\Models\User;

class UserCapabilityResolver
{
    public function allows(User $user, UserCapability $capability, int $departmentId): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->capabilityGrants()
            ->where('capability', $capability->value)
            ->where('department_id', $departmentId)
            ->where('is_active', true)
            ->exists();
    }
}
