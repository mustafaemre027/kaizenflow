<?php

namespace Tests\Feature\Kaizens\Implementation;

use App\Actions\Kaizens\AssignKaizenImplementation;
use App\Enums\KaizenStatus;
use App\Enums\UserCapability;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\KaizenWorkflowTransition;
use App\Models\User;
use App\Models\UserCapabilityGrant;
use App\Services\AppendAuditLog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignKaizenImplementationTest extends TestCase
{
    use RefreshDatabase;

    private AssignKaizenImplementation $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = app(AssignKaizenImplementation::class);
    }

    public function test_authorized_user_can_assign_with_capability_grant()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::APPROVED, 'department_id' => $department->id]);
        $user = User::factory()->create(['is_active' => true]);

        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN,
            'is_active' => true,
        ]);

        $assignee = User::factory()->create(['is_active' => true]);
        $targetDate = now()->addDays(5)->format('Y-m-d');

        $kaizen = $this->action->execute($kaizen, $user, $assignee->id, $targetDate);

        $this->assertEquals($assignee->id, $kaizen->assigned_user_id);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $user->id,
            'auditable_type' => Kaizen::class,
            'auditable_id' => $kaizen->id,
            'event' => 'implementation.assigned',
        ]);

        // Ensure NO fake status history
        $this->assertDatabaseMissing('kaizen_status_histories', [
            'kaizen_id' => $kaizen->id,
            'transition_code' => 'ASSIGN_IMPLEMENTATION',
        ]);
    }

    public function test_audit_rollback_on_failure()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::APPROVED, 'department_id' => $department->id]);
        $user = User::factory()->create(['is_active' => true]);

        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN,
            'is_active' => true,
        ]);

        $assignee = User::factory()->create(['is_active' => true]);
        $targetDate = now()->addDays(5)->format('Y-m-d');

        // Force an exception during audit log creation by dropping the table temporarily or mocking
        $this->mock(AppendAuditLog::class, function ($mock) {
            $mock->shouldReceive('execute')->andThrow(new \Exception('Audit failure'));
        });

        try {
            $this->action->execute($kaizen, $user, $assignee->id, $targetDate);
        } catch (\Exception $e) {
            // expected
        }

        // Must rollback assigned user
        $this->assertNull($kaizen->fresh()->assigned_user_id);
    }

    public function test_opex_specialist_cannot_assign_without_grant()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::APPROVED, 'department_id' => $department->id]);
        $user = User::factory()->create(['role' => UserRole::OPEX_SPECIALIST, 'is_active' => true]);
        $assignee = User::factory()->create();

        $this->expectException(AuthorizationException::class);
        $this->action->execute($kaizen, $user, $assignee->id, now()->addDays(1)->format('Y-m-d'));
    }

    public function test_manager_cannot_assign_without_grant()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::APPROVED, 'department_id' => $department->id]);
        $user = User::factory()->create(['role' => UserRole::MANAGER, 'is_active' => true, 'department_id' => $department->id]);
        $assignee = User::factory()->create();

        $this->expectException(AuthorizationException::class);
        $this->action->execute($kaizen, $user, $assignee->id, now()->addDays(1)->format('Y-m-d'));
    }

    public function test_admin_cannot_assign_without_grant()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::APPROVED, 'department_id' => $department->id]);
        $user = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);
        $assignee = User::factory()->create();

        $this->expectException(AuthorizationException::class);
        $this->action->execute($kaizen, $user, $assignee->id, now()->addDays(1)->format('Y-m-d'));
    }

    public function test_assign_grant_does_not_give_start_or_complete_capability()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::APPROVED, 'department_id' => $department->id]);
        $user = User::factory()->create(['is_active' => true]);

        // Has start but not assign
        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_START,
        ]);
        $assignee = User::factory()->create();

        $this->expectException(AuthorizationException::class);
        $this->action->execute($kaizen, $user, $assignee->id, now()->addDays(1)->format('Y-m-d'));
    }

    public function test_historical_reviewer_cannot_assign_even_with_grant()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::APPROVED, 'department_id' => $department->id]);
        $reviewer = User::factory()->create(['is_active' => true]);

        UserCapabilityGrant::factory()->create([
            'user_id' => $reviewer->id,
            'department_id' => $department->id,
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN,
        ]);

        KaizenWorkflowTransition::factory()->create([
            'kaizen_id' => $kaizen->id,
            'actor_user_id' => $reviewer->id,
        ]);

        $assignee = User::factory()->create();
        $this->expectException(AuthorizationException::class);
        $this->action->execute($kaizen, $reviewer, $assignee->id, now()->addDays(1)->format('Y-m-d'));
    }

    public function test_cannot_assign_with_grant_from_different_department()
    {
        $department = Department::factory()->create();
        $otherDept = Department::factory()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::APPROVED, 'department_id' => $department->id]);
        $user = User::factory()->create(['is_active' => true]);

        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $otherDept->id,
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN,
        ]);

        $assignee = User::factory()->create();
        $this->expectException(AuthorizationException::class);
        $this->action->execute($kaizen, $user, $assignee->id, now()->addDays(1)->format('Y-m-d'));
    }

    public function test_cannot_assign_with_inactive_grant()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::APPROVED, 'department_id' => $department->id]);
        $user = User::factory()->create(['is_active' => true]);

        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN,
            'is_active' => false,
        ]);

        $assignee = User::factory()->create();
        $this->expectException(AuthorizationException::class);
        $this->action->execute($kaizen, $user, $assignee->id, now()->addDays(1)->format('Y-m-d'));
    }

    public function test_assignee_cannot_assign_themselves_or_others()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::APPROVED, 'department_id' => $department->id]);
        $employee = User::factory()->create(['is_active' => true]);
        $kaizen->assigned_user_id = $employee->id;
        $kaizen->save();

        $this->expectException(AuthorizationException::class);
        $this->action->execute($kaizen, $employee, $employee->id, now()->addDays(1)->format('Y-m-d'));
    }

    public function test_cannot_assign_inactive_user()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::APPROVED, 'department_id' => $department->id]);
        $user = User::factory()->create(['is_active' => true]);

        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN,
        ]);
        $assignee = User::factory()->create(['is_active' => false]);

        $this->expectException(\Exception::class);
        $this->action->execute($kaizen, $user, $assignee->id, now()->addDays(1)->format('Y-m-d'));
    }

    public function test_cannot_assign_if_status_is_not_approved()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['is_active' => true]);
        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN,
        ]);
        $assignee = User::factory()->create();

        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::IN_PROGRESS, 'department_id' => $department->id]);
        try {
            $this->action->execute($kaizen, $user, $assignee->id, now()->addDays(1)->format('Y-m-d'));
            $this->fail('Should fail');
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function test_past_target_date_is_rejected()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::APPROVED, 'department_id' => $department->id]);
        $user = User::factory()->create(['is_active' => true]);
        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN,
        ]);
        $assignee = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->action->execute($kaizen, $user, $assignee->id, now()->subDay()->format('Y-m-d'));
    }

    public function test_second_assignment_does_not_silently_override()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::APPROVED,
            'department_id' => $department->id,
            'assigned_user_id' => User::factory()->create()->id,
            'target_date' => now()->addDays(2),
        ]);

        $user = User::factory()->create(['is_active' => true]);
        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN,
        ]);
        $assignee = User::factory()->create();

        $this->expectException(\Exception::class);
        $this->action->execute($kaizen, $user, $assignee->id, now()->addDays(1)->format('Y-m-d'));
    }
}
