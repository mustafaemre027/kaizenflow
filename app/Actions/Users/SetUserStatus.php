<?php

namespace App\Actions\Users;

use App\Enums\UserCapability;
use App\Exceptions\LastAuthorizationManagerException;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use DomainException;
use Illuminate\Support\Facades\DB;

class SetUserStatus
{
    public function execute(User $actor, User $target, bool $desiredActive): array
    {
        if (! $actor->is_active) {
            throw new DomainException('Actor is not active.');
        }

        if ($actor->id === $target->id) {
            throw new DomainException('Cannot change your own status from user management.');
        }

        return DB::transaction(function () use ($actor, $target, $desiredActive) {
            $lockedTarget = User::where('id', $target->id)->lockForUpdate()->first();
            if (! $lockedTarget) {
                throw new DomainException('Target user not found.');
            }

            if ($lockedTarget->is_active === $desiredActive) {
                return ['success' => true, 'message' => 'Kullanıcı durumu zaten istenilen şekilde.'];
            }

            if ($desiredActive === false) {
                // Last Authorization Manager Check
                $isAuthManager = UserSystemCapabilityGrant::where('user_id', $lockedTarget->id)
                    ->where('capability', UserCapability::AUTHORIZATION_MANAGE->value)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->exists();

                if ($isAuthManager) {
                    $activeManagersCount = UserSystemCapabilityGrant::join('users', 'users.id', '=', 'user_system_capability_grants.user_id')
                        ->where('user_system_capability_grants.capability', UserCapability::AUTHORIZATION_MANAGE->value)
                        ->where('user_system_capability_grants.is_active', true)
                        ->where('users.is_active', true)
                        ->lockForUpdate()
                        ->count();

                    if ($activeManagersCount <= 1) {
                        throw new LastAuthorizationManagerException;
                    }
                }

                // Session Invalidation
                $lockedTarget->remember_token = null;
            }

            $oldIsActive = $lockedTarget->is_active;
            $lockedTarget->is_active = $desiredActive;
            $lockedTarget->save();

            $this->writeAudit($actor, $lockedTarget, $oldIsActive, $desiredActive);

            return [
                'success' => true,
                'message' => 'Kullanıcı durumu güncellendi.',
            ];
        });
    }

    protected function writeAudit(User $actor, User $target, bool $oldIsActive, bool $newIsActive): void
    {
        $audit = new AuditLog([
            'actor_user_id' => $actor->id,
            'auditable_type' => User::class,
            'auditable_id' => $target->id,
            'event' => 'user.status_changed',
            'metadata' => [
                'target_user_id' => $target->id,
                'old_is_active' => $oldIsActive,
                'new_is_active' => $newIsActive,
            ],
        ]);

        $audit->save();
    }
}
