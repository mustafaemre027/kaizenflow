<?php

namespace App\Actions\Authorization;

use App\Enums\UserCapability;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class BootstrapSystemCapabilities
{
    public const PACKAGE = [
        UserCapability::AUTHORIZATION_MANAGE,
        UserCapability::ORGANIZATION_VIEW,
        UserCapability::ORGANIZATION_MANAGE,
        UserCapability::APPROVAL_CONFIGURATION_VIEW,
        UserCapability::APPROVAL_CONFIGURATION_MANAGE,
    ];

    public function execute(User $targetUser): void
    {
        DB::transaction(function () use ($targetUser) {
            $activeUserIds = User::where('is_active', true)->pluck('id')->toArray();
            $activeUserIds[] = $targetUser->id;

            $userIdsToLock = array_unique($activeUserIds);
            sort($userIdsToLock);

            // 1. Lock Users
            $lockedUsers = User::whereIn('id', $userIdsToLock)->orderBy('id')->lockForUpdate()->get();
            $freshTarget = $lockedUsers->firstWhere('id', $targetUser->id);

            if (! $freshTarget || ! $freshTarget->is_active) {
                throw new InvalidArgumentException('Target user must be active.');
            }

            // 2. Lock Grants
            $lockedGrants = UserSystemCapabilityGrant::whereIn('user_id', $userIdsToLock)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            // Calculate active managers
            $activeManagerIds = [];
            foreach ($lockedGrants as $grant) {
                if ($grant->capability === UserCapability::AUTHORIZATION_MANAGE && $grant->is_active) {
                    $user = $lockedUsers->firstWhere('id', $grant->user_id);
                    if ($user && $user->is_active) {
                        $activeManagerIds[] = $user->id;
                    }
                }
            }
            $activeManagerIds = array_unique($activeManagerIds);

            // Invariant check
            if (count($activeManagerIds) > 1) {
                throw new RuntimeException('bootstrap rejected: Multiple active managers exist.');
            }
            if (count($activeManagerIds) === 1 && $activeManagerIds[0] !== $freshTarget->id) {
                throw new RuntimeException('bootstrap rejected: Another active manager exists.');
            }

            // Perform Package Grants for the Target
            $targetGrants = $lockedGrants->where('user_id', $freshTarget->id);

            $createdCount = 0;
            $reactivatedCount = 0;
            $unchangedCount = 0;

            $oldStates = [];
            $newStates = [];

            // Sort capabilities to be deterministic
            $sortedPackage = self::PACKAGE;
            usort($sortedPackage, fn ($a, $b) => strcmp($a->value, $b->value));

            foreach ($sortedPackage as $capability) {
                $existingGrant = $targetGrants->firstWhere('capability', $capability);

                if (! $existingGrant) {
                    UserSystemCapabilityGrant::create([
                        'user_id' => $freshTarget->id,
                        'capability' => $capability,
                        'granted_by_user_id' => null,
                        'is_active' => true,
                    ]);
                    $oldStates[$capability->value] = null;
                    $newStates[$capability->value] = true;
                    $createdCount++;
                } elseif (! $existingGrant->is_active) {
                    $existingGrant->update([
                        'is_active' => true,
                        'granted_by_user_id' => null,
                    ]);
                    $oldStates[$capability->value] = false;
                    $newStates[$capability->value] = true;
                    $reactivatedCount++;
                } else {
                    $oldStates[$capability->value] = true;
                    $newStates[$capability->value] = true;
                    $unchangedCount++;
                }
            }

            // Only audit if actual changes happened
            if ($createdCount > 0 || $reactivatedCount > 0) {
                $metadata = [
                    'target_user_id' => $freshTarget->id,
                    'scope' => 'system',
                    'source' => 'artisan',
                    'command' => 'capability:bootstrap-admin',
                    'capabilities' => array_map(fn ($c) => $c->value, $sortedPackage),
                    'old_states' => $oldStates,
                    'new_states' => $newStates,
                    'created_count' => $createdCount,
                    'reactivated_count' => $reactivatedCount,
                    'unchanged_count' => $unchangedCount,
                ];

                $this->writeAudit($freshTarget, $metadata);
            }
        });
    }

    protected function writeAudit(User $target, array $metadata): void
    {
        $audit = new AuditLog([
            'actor_user_id' => null,
            'auditable_type' => User::class,
            'auditable_id' => $target->id,
            'event' => 'authorization.system_capabilities.bootstrapped',
            'metadata' => $metadata,
        ]);

        $audit->save();
    }
}
