<?php

namespace Tests\Feature\Kaizens\Implementation;

use App\Actions\Kaizens\StartKaizenImplementation;
use App\Enums\KaizenStatus;
use App\Enums\UserCapability;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\KaizenWorkflowTransition;
use App\Models\User;
use App\Models\UserCapabilityGrant;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StartKaizenImplementationTest extends TestCase
{
    use RefreshDatabase;

    private StartKaizenImplementation $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = app(StartKaizenImplementation::class);
    }

    public function test_authorized_user_can_start_with_grant()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::APPROVED,
            'department_id' => $department->id,
            'assigned_user_id' => User::factory()->create()->id,
            'target_date' => now()->addDays(5),
        ]);

        $user = User::factory()->create(['is_active' => true]);
        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_START,
        ]);

        $kaizen = $this->action->execute($kaizen, $user);

        $this->assertEquals(KaizenStatus::IN_PROGRESS, $kaizen->status);
        $this->assertNotNull($kaizen->started_at);
    }

    public function test_cannot_start_without_grant()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::APPROVED,
            'department_id' => $department->id,
            'assigned_user_id' => User::factory()->create()->id,
            'target_date' => now()->addDays(5),
        ]);

        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::OPEX_SPECIALIST]);

        $this->expectException(AuthorizationException::class);
        $this->action->execute($kaizen, $user);
    }

    public function test_assignee_cannot_start_without_grant()
    {
        $department = Department::factory()->create();
        $assignee = User::factory()->create(['is_active' => true]);

        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::APPROVED,
            'department_id' => $department->id,
            'assigned_user_id' => $assignee->id,
            'target_date' => now()->addDays(5),
        ]);

        $this->expectException(AuthorizationException::class);
        $this->action->execute($kaizen, $assignee);
    }

    public function test_start_grant_does_not_give_complete_capability()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::APPROVED,
            'department_id' => $department->id,
            'assigned_user_id' => User::factory()->create()->id,
            'target_date' => now()->addDays(5),
        ]);

        $user = User::factory()->create(['is_active' => true]);
        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN, // wrong grant
        ]);

        $this->expectException(AuthorizationException::class);
        $this->action->execute($kaizen, $user);
    }

    public function test_historical_reviewer_cannot_start()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::APPROVED,
            'department_id' => $department->id,
            'assigned_user_id' => User::factory()->create()->id,
            'target_date' => now()->addDays(5),
        ]);

        $reviewer = User::factory()->create(['is_active' => true]);
        UserCapabilityGrant::factory()->create([
            'user_id' => $reviewer->id,
            'department_id' => $department->id,
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_START,
        ]);

        KaizenWorkflowTransition::factory()->create([
            'kaizen_id' => $kaizen->id,
            'actor_user_id' => $reviewer->id,
        ]);

        $this->expectException(AuthorizationException::class);
        $this->action->execute($kaizen, $reviewer);
    }

    public function test_cannot_start_unassigned_kaizen()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::APPROVED, 'department_id' => $department->id]);

        $user = User::factory()->create(['is_active' => true]);
        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_START,
        ]);

        $this->expectException(\Exception::class);
        $this->action->execute($kaizen, $user);
    }

    public function test_cannot_start_without_target_date()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::APPROVED,
            'department_id' => $department->id,
            'assigned_user_id' => User::factory()->create()->id,
        ]);

        $user = User::factory()->create(['is_active' => true]);
        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_START,
        ]);

        $this->expectException(\Exception::class);
        $this->action->execute($kaizen, $user);
    }

    public function test_cannot_start_if_assignee_is_inactive()
    {
        $department = Department::factory()->create();
        $assignee = User::factory()->create(['is_active' => false]);
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::APPROVED,
            'department_id' => $department->id,
            'assigned_user_id' => $assignee->id,
            'target_date' => now()->addDays(5),
        ]);

        $user = User::factory()->create(['is_active' => true]);
        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_START,
        ]);

        $this->expectException(\Exception::class);
        $this->action->execute($kaizen, $user);
    }

    public function test_wrong_status_cannot_be_started()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::IN_PROGRESS,
            'department_id' => $department->id,
            'assigned_user_id' => User::factory()->create()->id,
            'target_date' => now()->addDays(5),
        ]);

        $user = User::factory()->create(['is_active' => true]);
        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_START,
        ]);

        $this->expectException(\Exception::class);
        $this->action->execute($kaizen, $user);
    }

    public function test_stale_start_does_not_create_second_history_record()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::IN_PROGRESS, // Already in progress
            'department_id' => $department->id,
            'assigned_user_id' => User::factory()->create()->id,
            'target_date' => now()->addDays(5),
        ]);

        $user = User::factory()->create(['is_active' => true]);
        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_START,
        ]);

        try {
            $this->action->execute($kaizen, $user);
        } catch (\Exception $e) {
            //
        }

        $this->assertDatabaseMissing('kaizen_status_histories', [
            'kaizen_id' => $kaizen->id,
            'transition_code' => 'START_IMPLEMENTATION',
        ]);
    }
}
