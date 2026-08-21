<?php

namespace App\Actions\Authorization;

use App\Enums\CapabilityScope;
use App\Enums\UserCapability;
use App\Exceptions\ScopeMismatchException;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use App\Services\AppendAuditLog;
use App\Services\UserCapabilityResolver;
use Exception;
use Illuminate\Support\Facades\DB;

class GrantSystemCapability
{
    public function __construct(
        private AppendAuditLog $auditLog
    ) {}

    public function execute(User $actor, User $target, UserCapability $capability): void
    {
        if ($capability->scope() !== CapabilityScope::SYSTEM) {
            throw new ScopeMismatchException("The capability '{$capability->value}' is not a SYSTEM capability.");
        }

        if ($actor->id === $target->id) {
            throw new Exception('Unauthorized action.');
        }

        DB::transaction(function () use ($actor, $target, $capability) {
            $firstId = min($actor->id, $target->id);
            $secondId = max($actor->id, $target->id);

            $users = User::whereIn('id', [$firstId, $secondId])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $freshActor = $users->get($actor->id);
            $freshTarget = $users->get($target->id);

            if (! $freshActor || ! $freshActor->is_active) {
                throw new Exception('Unauthorized action.');
            }

            if (! $freshTarget || ! $freshTarget->is_active) {
                throw new Exception('Unauthorized action.');
            }

            $requiredCapabilities = array_unique([UserCapability::AUTHORIZATION_MANAGE->value, $capability->value]);
            
            $actorGrants = UserSystemCapabilityGrant::where('user_id', $freshActor->id)
                ->whereIn('capability', $requiredCapabilities)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('capability');

            if (! $actorGrants->has(UserCapability::AUTHORIZATION_MANAGE->value) || ! $actorGrants->get(UserCapability::AUTHORIZATION_MANAGE->value)->is_active) {
                throw new Exception('Unauthorized action.');
            }

            if (! $actorGrants->has($capability->value) || ! $actorGrants->get($capability->value)->is_active) {
                throw new Exception('Unauthorized action.');
            }

            $grant = UserSystemCapabilityGrant::where('user_id', $freshTarget->id)
                ->where('capability', $capability->value)
                ->lockForUpdate()
                ->first();

            if ($grant && $grant->is_active) {
                return;
            }

            $oldIsActive = $grant ? false : null;

            if ($grant) {
                $grant->is_active = true;
                $grant->granted_by_user_id = $actor->id;
                $grant->save();
            } else {
                $grant = UserSystemCapabilityGrant::create([
                    'user_id' => $target->id,
                    'capability' => $capability->value,
                    'granted_by_user_id' => $actor->id,
                    'is_active' => true,
                ]);
            }

            $this->auditLog->execute($actor, $grant, 'authorization.system_capability.granted', [
                'actor_user_id' => $actor->id,
                'target_user_id' => $target->id,
                'capability' => $capability->value,
                'scope' => 'system',
                'old_is_active' => $oldIsActive,
                'new_is_active' => true,
            ]);
        });
    }
}
