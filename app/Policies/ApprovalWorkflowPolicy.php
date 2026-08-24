<?php

namespace App\Policies;

use App\Enums\UserCapability;
use App\Models\User;
use App\Services\UserCapabilityResolver;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApprovalWorkflowPolicy
{
    use HandlesAuthorization;

    public function __construct(private UserCapabilityResolver $capabilityResolver) {}

    public function viewAny(User $user): bool
    {
        return $this->capabilityResolver->allowsSystem($user, UserCapability::APPROVAL_CONFIGURATION_VIEW);
    }

    public function view(User $user): bool
    {
        return $this->capabilityResolver->allowsSystem($user, UserCapability::APPROVAL_CONFIGURATION_VIEW);
    }
}
