<?php

namespace App\Policies;

use App\Enums\UserCapability;
use App\Models\User;
use App\Services\UserCapabilityResolver;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function __construct(
        private UserCapabilityResolver $capabilityResolver
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->hasRequiredCapabilities($user);
    }

    public function create(User $user): bool
    {
        return $this->hasRequiredCapabilities($user);
    }

    public function view(User $user, User $model): bool
    {
        return $this->hasRequiredCapabilities($user);
    }

    public function update(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $this->hasRequiredCapabilities($user);
    }

    public function setStatus(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $this->hasRequiredCapabilities($user);
    }

    public function resendInvitation(User $actor, User $target): bool
    {
        return $this->update($actor, $target);
    }

    public function viewCapabilities(User $actor, User $target): bool
    {
        if (! $actor->is_active) {
            return false;
        }

        if (! $this->capabilityResolver->allowsSystem($actor, UserCapability::AUTHORIZATION_MANAGE)) {
            return false;
        }

        if (! $this->capabilityResolver->allowsSystem($actor, UserCapability::ORGANIZATION_VIEW)) {
            return false;
        }

        return true;
    }

    public function manageCapabilities(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return false;
        }

        return $this->viewCapabilities($actor, $target);
    }

    private function hasRequiredCapabilities(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $this->capabilityResolver->allowsSystem($user, UserCapability::AUTHORIZATION_MANAGE) &&
               $this->capabilityResolver->allowsSystem($user, UserCapability::ORGANIZATION_VIEW);
    }
}
