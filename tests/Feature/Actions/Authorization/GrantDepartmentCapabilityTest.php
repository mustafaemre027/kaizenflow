<?php

namespace Tests\Feature\Actions\Authorization;

use App\Actions\Authorization\GrantDepartmentCapability;
use App\Enums\UserCapability;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use App\Models\UserCapabilityGrant;
use App\Models\UserSystemCapabilityGrant;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GrantDepartmentCapabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    private User $target;

    private Department $department;

    private GrantDepartmentCapability $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::factory()->create(['is_active' => true]);
        $this->target = User::factory()->create(['is_active' => true]);
        $this->department = Department::factory()->create(['is_active' => true]);

        $this->action = app(GrantDepartmentCapability::class);
    }

    private function giveCentralAdminAuthority(User $user): void
    {
        UserSystemCapabilityGrant::create([
            'user_id' => $user->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
            'is_active' => true,
        ]);
        UserSystemCapabilityGrant::create([
            'user_id' => $user->id,
            'capability' => UserCapability::ORGANIZATION_VIEW,
            'is_active' => true,
        ]);
        UserSystemCapabilityGrant::create([
            'user_id' => $user->id,
            'capability' => UserCapability::ORGANIZATION_MANAGE,
            'is_active' => true,
        ]);
    }

    #[DataProvider('departmentCapabilityProvider')]
    public function test_it_grants_department_capability(UserCapability $capability)
    {
        $this->giveCentralAdminAuthority($this->actor);

        $this->action->execute($this->actor, $this->target, $this->department, $capability);

        $this->assertDatabaseHas('user_capability_grants', [
            'user_id' => $this->target->id,
            'department_id' => $this->department->id,
            'capability' => $capability->value,
            'is_active' => true,
            'granted_by_user_id' => $this->actor->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'authorization.department_capability.granted',
            'actor_user_id' => $this->actor->id,
            'auditable_id' => UserCapabilityGrant::first()->id,
        ]);
    }

    public static function departmentCapabilityProvider(): array
    {
        return [
            [UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN],
            [UserCapability::KAIZEN_IMPLEMENTATION_START],
            [UserCapability::KAIZEN_IMPLEMENTATION_COMPLETE],
            [UserCapability::KAIZEN_DEPARTMENT_APPROVE],
        ];
    }

    public function test_it_rejects_granting_if_actor_is_missing_organization_manage()
    {
        UserSystemCapabilityGrant::create([
            'user_id' => $this->actor->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
            'is_active' => true,
        ]);
        UserSystemCapabilityGrant::create([
            'user_id' => $this->actor->id,
            'capability' => UserCapability::ORGANIZATION_VIEW,
            'is_active' => true,
        ]);
        // Missing ORGANIZATION_MANAGE

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unauthorized action.');

        $this->action->execute($this->actor, $this->target, $this->department, UserCapability::KAIZEN_DEPARTMENT_APPROVE);
    }

    public function test_it_rejects_granting_if_target_is_inactive()
    {
        $this->giveCentralAdminAuthority($this->actor);
        $this->target->is_active = false;
        $this->target->save();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unauthorized action.');

        $this->action->execute($this->actor, $this->target, $this->department, UserCapability::KAIZEN_DEPARTMENT_APPROVE);
    }

    public function test_it_rejects_self_grant()
    {
        $this->giveCentralAdminAuthority($this->actor);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unauthorized action.');

        $this->action->execute($this->actor, $this->actor, $this->department, UserCapability::KAIZEN_DEPARTMENT_APPROVE);
    }

    public function test_it_does_not_duplicate_existing_active_grant()
    {
        $this->giveCentralAdminAuthority($this->actor);

        $this->action->execute($this->actor, $this->target, $this->department, UserCapability::KAIZEN_DEPARTMENT_APPROVE);

        // Try again
        $this->action->execute($this->actor, $this->target, $this->department, UserCapability::KAIZEN_DEPARTMENT_APPROVE);

        $this->assertEquals(1, UserCapabilityGrant::where('user_id', $this->target->id)->count());
        $this->assertEquals(1, AuditLog::where('event', 'authorization.department_capability.granted')->count());
    }

    public function test_it_reactivates_inactive_grant()
    {
        $this->giveCentralAdminAuthority($this->actor);

        $grant = UserCapabilityGrant::create([
            'user_id' => $this->target->id,
            'department_id' => $this->department->id,
            'capability' => UserCapability::KAIZEN_DEPARTMENT_APPROVE,
            'is_active' => false,
            'granted_by_user_id' => $this->actor->id,
        ]);

        $this->action->execute($this->actor, $this->target, $this->department, UserCapability::KAIZEN_DEPARTMENT_APPROVE);

        $this->assertDatabaseHas('user_capability_grants', [
            'id' => $grant->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'authorization.department_capability.granted',
        ]);
    }
}
