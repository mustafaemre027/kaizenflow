<?php

namespace App\Actions\ApprovalConfiguration;

use App\Enums\UserCapability;
use App\Exceptions\AuthorizationException;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use App\Services\UserCapabilityResolver;

trait HasApprovalConfigurationMutation
{
    protected function authorizeAndLock(User $actor, UserCapabilityResolver $resolver): void
    {
        $actorLocked = User::where('id', $actor->id)->lockForUpdate()->first();
        if (! $actorLocked || ! $actorLocked->is_active) {
            throw new AuthorizationException('Actor is inactive or not found.');
        }

        UserSystemCapabilityGrant::where('user_id', $actor->id)
            ->where('capability', UserCapability::APPROVAL_CONFIGURATION_MANAGE)
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        if (! $resolver->allowsSystem($actorLocked, UserCapability::APPROVAL_CONFIGURATION_MANAGE)) {
            throw new AuthorizationException('Actor does not have manage approval configuration capability.');
        }
    }
}
