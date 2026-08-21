<?php

namespace App\Actions\Authorization;

use App\Enums\CapabilityScope;
use App\Enums\UserCapability;
use App\Exceptions\LastAuthorizationManagerException;
use App\Exceptions\ScopeMismatchException;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use App\Services\AppendAuditLog;
use App\Services\UserCapabilityResolver;
use Exception;
use Illuminate\Support\Facades\DB;

class RevokeSystemCapability
{
    public function __construct(
        private AppendAuditLog $auditLog
    ) {}

    public function execute(User $actor, User $target, UserCapability $capability): void
    {
        if ($capability->scope() !== CapabilityScope::SYSTEM) {
            throw new ScopeMismatchException("The capability '{$capability->value}' is not a SYSTEM capability.");
        }

        DB::transaction(function () use ($actor, $target, $capability) {
            $userIdsToLock = [$actor->id, $target->id];

            if ($capability === UserCapability::AUTHORIZATION_MANAGE) {
                $managerIds = DB::table('user_system_capability_grants')
                    ->join('users', 'users.id', '=', 'user_system_capability_grants.user_id')
                    ->where('user_system_capability_grants.capability', UserCapability::AUTHORIZATION_MANAGE->value)
                    ->where('user_system_capability_grants.is_active', true)
                    ->where('users.is_active', true)
                    ->pluck('users.id')
                    ->toArray();

                $userIdsToLock = array_merge($userIdsToLock, $managerIds);
            }

            $userIdsToLock = array_unique($userIdsToLock);
            sort($userIdsToLock);

            $users = User::whereIn('id', $userIdsToLock)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $freshActor = $users->get($actor->id);
            $freshTarget = $users->get($target->id);

            if (! $freshActor || ! $freshActor->is_active) {
                throw new Exception('Unauthorized action.');
            }

            $userIdsForGrantsToLock = $capability === UserCapability::AUTHORIZATION_MANAGE 
                ? $userIdsToLock 
                : [$freshActor->id];

            $managerGrants = UserSystemCapabilityGrant::whereIn('user_id', $userIdsForGrantsToLock)
                ->where('capability', UserCapability::AUTHORIZATION_MANAGE->value)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('user_id');

            if (! $managerGrants->has($freshActor->id) || ! $managerGrants->get($freshActor->id)->is_active) {
                throw new Exception('Unauthorized action.');
            }

            $grant = UserSystemCapabilityGrant::where('user_id', $freshTarget->id)
                ->where('capability', $capability->value)
                ->lockForUpdate()
                ->first();

            if (! $grant || ! $grant->is_active) {
                return;
            }

            if ($capability === UserCapability::AUTHORIZATION_MANAGE) {
                $activeManagersCount = UserSystemCapabilityGrant::join('users', 'users.id', '=', 'user_system_capability_grants.user_id')
                    ->where('user_system_capability_grants.capability', UserCapability::AUTHORIZATION_MANAGE->value)
                    ->where('user_system_capability_grants.is_active', true)
                    ->where('users.is_active', true)
                    ->lockForUpdate()
                    ->count();

                if ($freshTarget->is_active) {
                    if ($activeManagersCount <= 1) {
                        throw new LastAuthorizationManagerException;
                    }
                }
            }

            $grant->is_active = false;
            $grant->save();

            $this->auditLog->execute($actor, $grant, 'authorization.system_capability.revoked', [
                'actor_user_id' => $actor->id,
                'target_user_id' => $target->id,
                'capability' => $capability->value,
                'scope' => 'system',
                'old_is_active' => true,
                'new_is_active' => false,
            ]);
        });
    }
}
