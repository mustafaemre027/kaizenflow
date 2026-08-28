<?php

namespace Tests\Feature\Kaizens\Implementation;

use App\Actions\Kaizens\CompleteKaizenImplementation;
use App\Enums\KaizenStatus;
use App\Enums\UserCapability;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\User;
use App\Models\UserCapabilityGrant;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CompleteKaizenImplementationTest extends TestCase
{
    use RefreshDatabase;

    private CompleteKaizenImplementation $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = app(CompleteKaizenImplementation::class);
    }

    public function test_authorized_user_can_complete_with_grant()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::IN_PROGRESS,
            'department_id' => $department->id,
            'assigned_user_id' => User::factory()->create()->id,
            'target_date' => now()->addDays(5),
            'started_at' => now()->subDay(),
        ]);

        $user = User::factory()->create(['is_active' => true]);
        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_COMPLETE,
        ]);

        $kaizen = $this->action->execute($kaizen, $user, 'All done!');

        $this->assertEquals(KaizenStatus::COMPLETED, $kaizen->status);
        $this->assertNotNull($kaizen->completed_at);
        $this->assertEquals('All done!', $kaizen->actual_result);
    }

    public function test_cannot_complete_without_grant()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::IN_PROGRESS,
            'department_id' => $department->id,
            'assigned_user_id' => User::factory()->create()->id,
            'target_date' => now()->addDays(5),
            'started_at' => now()->subDay(),
        ]);

        $user = User::factory()->create(['is_active' => true]);

        $this->expectException(AuthorizationException::class);
        $this->action->execute($kaizen, $user, 'Done');
    }

    public function test_assignee_cannot_complete_just_because_they_are_assigned()
    {
        $department = Department::factory()->create();
        $assignee = User::factory()->create(['is_active' => true]);

        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::IN_PROGRESS,
            'department_id' => $department->id,
            'assigned_user_id' => $assignee->id,
            'target_date' => now()->addDays(5),
            'started_at' => now()->subDay(),
        ]);

        $this->expectException(AuthorizationException::class);
        $this->action->execute($kaizen, $assignee, 'Done');
    }

    public function test_cannot_complete_without_actual_result()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::IN_PROGRESS,
            'department_id' => $department->id,
            'assigned_user_id' => User::factory()->create()->id,
            'target_date' => now()->addDays(5),
            'started_at' => now()->subDay(),
        ]);

        $user = User::factory()->create(['is_active' => true]);
        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_COMPLETE,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->action->execute($kaizen, $user, '');
    }

    public function test_cannot_complete_if_not_in_progress()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::APPROVED, // Not in progress
            'department_id' => $department->id,
            'assigned_user_id' => User::factory()->create()->id,
            'target_date' => now()->addDays(5),
        ]);

        $user = User::factory()->create(['is_active' => true]);
        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_COMPLETE,
        ]);

        $this->expectException(\DomainException::class);
        $this->action->execute($kaizen, $user, 'Done');
    }

    public function test_second_completion_is_rejected()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::COMPLETED, // Already completed
            'department_id' => $department->id,
            'assigned_user_id' => User::factory()->create()->id,
            'target_date' => now()->addDays(5),
            'started_at' => now()->subDay(),
            'completed_at' => now(),
            'actual_result' => 'Done before',
        ]);

        $user = User::factory()->create(['is_active' => true]);
        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_COMPLETE,
        ]);

        $this->expectException(\DomainException::class);
        $this->action->execute($kaizen, $user, 'Done again');
    }

    public function test_transaction_rolls_back_on_benefit_failure()
    {
        $department = Department::factory()->create();
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::IN_PROGRESS,
            'department_id' => $department->id,
            'assigned_user_id' => User::factory()->create()->id,
            'target_date' => now()->addDays(5),
            'started_at' => now()->subDay(),
        ]);

        $user = User::factory()->create(['is_active' => true]);
        UserCapabilityGrant::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_COMPLETE,
        ]);

        // Pass invalid benefit type ID to force a ValidationException inside the transaction
        try {
            $this->action->execute($kaizen, $user, 'All done!', [
                ['benefit_type_id' => 9999, 'realized_value' => '10'],
            ]);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            // Expected exception
        }

        $kaizen->refresh();
        $this->assertEquals(KaizenStatus::IN_PROGRESS, $kaizen->status);
        $this->assertNull($kaizen->completed_at);
        $this->assertNull($kaizen->actual_result);
        $this->assertDatabaseCount('kaizen_benefits', 0);
        $this->assertDatabaseCount('kaizen_status_histories', 0);
    }
}
