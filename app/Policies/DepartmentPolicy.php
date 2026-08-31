<?php

namespace App\Policies;

use App\Enums\UserCapability;
use App\Models\Department;
use App\Models\User;
use App\Services\UserCapabilityResolver;

class DepartmentPolicy
{
    public function __construct(private UserCapabilityResolver $resolver) {}

    public function viewAny(User $user): bool
    {
        return $this->resolver->allowsSystem($user, UserCapability::ORGANIZATION_VIEW);
    }

    public function view(User $user, Department $department): bool
    {
        return $this->resolver->allowsSystem($user, UserCapability::ORGANIZATION_VIEW);
    }

    public function create(User $user): bool
    {
        return $this->resolver->allowsSystem($user, UserCapability::ORGANIZATION_VIEW) &&
               $this->resolver->allowsSystem($user, UserCapability::ORGANIZATION_MANAGE);
    }

    public function update(User $user, Department $department): bool
    {
        return $this->resolver->allowsSystem($user, UserCapability::ORGANIZATION_VIEW) &&
               $this->resolver->allowsSystem($user, UserCapability::ORGANIZATION_MANAGE);
    }
}
