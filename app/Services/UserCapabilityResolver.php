<?php

namespace App\Services;

use App\Enums\UserCapability;
use App\Models\User;

class UserCapabilityResolver
{
    public function allowsSystem(User $user, UserCapability $capability): bool
    {
        if ($capability->scope() !== \App\Enums\CapabilityScope::SYSTEM) {
            throw new \App\Exceptions\ScopeMismatchException("The capability '{$capability->value}' is not a SYSTEM capability.");
        }

        if (!$user->is_active) {
            return false;
        }

        return \App\Models\UserSystemCapabilityGrant::where('user_id', $user->id)
            ->where('capability', $capability)
            ->where('is_active', true)
            ->exists();
    }

    public function allows(User $user, UserCapability $capability, int $departmentId): bool
    {
        if ($capability->scope() !== \App\Enums\CapabilityScope::DEPARTMENT) {
            throw new \App\Exceptions\ScopeMismatchException("The capability '{$capability->value}' is not a DEPARTMENT capability.");
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
