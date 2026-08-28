<?php

namespace App\Policies;

use App\Enums\UserCapability;
use App\Models\BenefitType;
use App\Models\User;
use App\Services\UserCapabilityResolver;

class BenefitTypePolicy
{
    public function __construct(private UserCapabilityResolver $resolver) {}

    public function viewAny(User $user): bool
    {
        return $this->resolver->allowsSystem($user, UserCapability::ORGANIZATION_VIEW);
    }

    public function view(User $user, BenefitType $benefitType): bool
    {
        return $this->resolver->allowsSystem($user, UserCapability::ORGANIZATION_VIEW);
    }

    public function create(User $user): bool
    {
        return $this->resolver->allowsSystem($user, UserCapability::ORGANIZATION_VIEW) &&
               $this->resolver->allowsSystem($user, UserCapability::ORGANIZATION_MANAGE);
    }

    public function update(User $user, BenefitType $benefitType): bool
    {
        return $this->resolver->allowsSystem($user, UserCapability::ORGANIZATION_VIEW) &&
               $this->resolver->allowsSystem($user, UserCapability::ORGANIZATION_MANAGE);
    }
}
