<?php

namespace App\Actions\Authorization;

use App\Enums\CapabilityScope;
use App\Enums\UserCapability;
use App\Exceptions\ScopeMismatchException;
use App\Models\Department;
use App\Models\User;
use App\Models\UserCapabilityGrant;
use App\Models\UserSystemCapabilityGrant;
use App\Services\AppendAuditLog;
use Exception;
use Illuminate\Support\Facades\DB;

class RevokeDepartmentCapability
{
    public function __construct(
        private AppendAuditLog $auditLog
    ) {}

    public function execute(User $actor, User $target, Department $department, UserCapability $capability): void
    {
        if ($capability->scope() !== CapabilityScope::DEPARTMENT) {
            throw new ScopeMismatchException("The capability '{$capability->value}' is not a DEPARTMENT capability.");
        }

        if ($actor->id === $target->id) {
            throw new Exception('Unauthorized action.');
        }

        DB::transaction(function () use ($actor, $target, $department, $capability) {
            $firstId = min($actor->id, $target->id);
            $secondId = max($actor->id, $target->id);

            $users = User::whereIn('id', [$firstId, $secondId])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $freshActor = $users->get($actor->id);
            $freshTarget = $users->get($target->id);

            $freshDepartment = Department::where('id', $department->id)->lockForUpdate()->first();

            if (! $freshActor || ! $freshActor->is_active) {
                throw new Exception('Unauthorized action.');
            }

            $requiredSystemCapabilities = [
                UserCapability::AUTHORIZATION_MANAGE->value,
                UserCapability::ORGANIZATION_VIEW->value,
                UserCapability::ORGANIZATION_MANAGE->value,
            ];

            $actorGrants = UserSystemCapabilityGrant::where('user_id', $freshActor->id)
                ->whereIn('capability', $requiredSystemCapabilities)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('capability');

            foreach ($requiredSystemCapabilities as $requiredCapability) {
                if (! $actorGrants->has($requiredCapability) || ! $actorGrants->get($requiredCapability)->is_active) {
                    throw new Exception('Unauthorized action.');
                }
            }

            $grant = UserCapabilityGrant::where('user_id', $freshTarget->id)
                ->where('department_id', $freshDepartment->id)
                ->where('capability', $capability->value)
                ->lockForUpdate()
                ->first();

            if (! $grant || ! $grant->is_active) {
                return;
            }

            $grant->is_active = false;
            $grant->save();

            $this->auditLog->execute($actor, $grant, 'authorization.department_capability.revoked', [
                'actor_user_id' => $actor->id,
                'target_user_id' => $target->id,
                'department_id' => $department->id,
                'capability' => $capability->value,
                'scope' => 'department',
                'old_is_active' => true,
                'new_is_active' => false,
            ]);
        });
    }
}
