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
        private UserCapabilityResolver $resolver,
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

        if (! $actor->is_active || ! $target->is_active) {
            throw new Exception('Unauthorized action.');
        }

        if (! $this->resolver->allowsSystem($actor, UserCapability::AUTHORIZATION_MANAGE)) {
            throw new Exception('Unauthorized action.');
        }

        if (! $this->resolver->allowsSystem($actor, $capability)) {
            throw new Exception('Unauthorized action.');
        }

        DB::transaction(function () use ($actor, $target, $capability) {
            $firstId = min($actor->id, $target->id);
            $secondId = max($actor->id, $target->id);

            User::whereIn('id', [$firstId, $secondId])->orderBy('id')->lockForUpdate()->get();

            $grant = UserSystemCapabilityGrant::where('user_id', $target->id)
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
