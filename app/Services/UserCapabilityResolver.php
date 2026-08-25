<?php

namespace App\Services;

use App\Enums\CapabilityScope;
use App\Enums\UserCapability;
use App\Exceptions\ScopeMismatchException;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;

class UserCapabilityResolver
{
    public function allowsSystem(User $user, UserCapability $capability): bool
    {
        if ($capability->scope() !== CapabilityScope::SYSTEM) {
            throw new ScopeMismatchException("The capability '{$capability->value}' is not a SYSTEM capability.");
        }

        if (! $user->is_active) {
            return false;
        }

        return UserSystemCapabilityGrant::where('user_id', $user->id)
            ->where('capability', $capability)
            ->where('is_active', true)
            ->exists();
    }

    public function allows(User $user, UserCapability $capability, int $departmentId): bool
    {
        if ($capability->scope() !== CapabilityScope::DEPARTMENT) {
            throw new ScopeMismatchException("The capability '{$capability->value}' is not a DEPARTMENT capability.");
        }

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
