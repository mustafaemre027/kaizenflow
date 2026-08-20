<?php

namespace Tests\Feature\Kaizens\Implementation;

use App\Actions\Kaizens\StartKaizenImplementation;
use App\Enums\KaizenStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\KaizenWorkflowTransition;
use App\Models\User;
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
        // Will create this class shortly
        $this->action = app(StartKaizenImplementation::class);
    }

    public function test_authorized_user_can_start_implementation_for_assigned_approved_kaizen()
    {
        $assignee = User::factory()->create();
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::APPROVED,
            'assigned_user_id' => $assignee->id,
            'target_date' => now()->addDays(5),
        ]);
        $opexUser = User::factory()->create(['role' => UserRole::OPEX_SPECIALIST, 'is_active' => true]);

        $kaizen = $this->action->execute($kaizen, $opexUser);

        $this->assertEquals(KaizenStatus::IN_PROGRESS, $kaizen->status);
        $this->assertNotNull($kaizen->started_at);
        $this->assertNull($kaizen->completed_at);

        // Exact one lifecycle history should be generated
        $this->assertDatabaseHas('kaizen_status_histories', [
            'kaizen_id' => $kaizen->id,
            'to_status' => KaizenStatus::IN_PROGRESS->value,
            'actor_user_id' => $opexUser->id,
        ]);
    }

    public function test_unauthorized_user_cannot_start()
    {
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::APPROVED,
            'assigned_user_id' => User::factory()->create()->id,
            'target_date' => now()->addDays(5),
        ]);
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE, 'is_active' => true]);

        $this->expectException(AuthorizationException::class);

        $this->action->execute($kaizen, $employee);
    }

    public function test_assignee_cannot_start_just_because_they_are_assigned()
    {
        $assignee = User::factory()->create(['role' => UserRole::EMPLOYEE, 'is_active' => true]);
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::APPROVED,
            'assigned_user_id' => $assignee->id,
            'target_date' => now()->addDays(5),
        ]);

        $this->expectException(AuthorizationException::class);

        $this->action->execute($kaizen, $assignee);
    }

    public function test_historical_reviewer_cannot_start()
    {
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::APPROVED,
            'assigned_user_id' => User::factory()->create()->id,
            'target_date' => now()->addDays(5),
        ]);
        $reviewer = User::factory()->create([
            'role' => UserRole::MANAGER,
            'department_id' => Department::factory()->create()->id,
        ]); // diff dept

        KaizenWorkflowTransition::factory()->create([
            'kaizen_id' => $kaizen->id,
            'actor_user_id' => $reviewer->id,
        ]);

        $this->expectException(AuthorizationException::class);

        $this->action->execute($kaizen, $reviewer);
    }

    public function test_cannot_start_unassigned_kaizen()
    {
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::APPROVED,
            'assigned_user_id' => null,
            'target_date' => now()->addDays(5),
        ]);
        $opexUser = User::factory()->create(['role' => UserRole::OPEX_SPECIALIST]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kaizen must have an assignee before starting implementation.');

        $this->action->execute($kaizen, $opexUser);
    }

    public function test_cannot_start_without_target_date()
    {
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::APPROVED,
            'assigned_user_id' => User::factory()->create()->id,
            'target_date' => null,
        ]);
        $opexUser = User::factory()->create(['role' => UserRole::OPEX_SPECIALIST]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kaizen must have a target date before starting implementation.');

        $this->action->execute($kaizen, $opexUser);
    }

    public function test_cannot_start_if_assignee_is_inactive()
    {
        $inactiveAssignee = User::factory()->create(['is_active' => false]);
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::APPROVED,
            'assigned_user_id' => $inactiveAssignee->id,
            'target_date' => now()->addDays(5),
        ]);
        $opexUser = User::factory()->create(['role' => UserRole::OPEX_SPECIALIST]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The assigned user is not active.');

        $this->action->execute($kaizen, $opexUser);
    }

    public function test_wrong_status_cannot_be_started()
    {
        $invalidStatuses = collect(KaizenStatus::cases())
            ->reject(fn ($s) => $s === KaizenStatus::APPROVED);

        $opexUser = User::factory()->create(['role' => UserRole::OPEX_SPECIALIST]);

        foreach ($invalidStatuses as $status) {
            $kaizen = Kaizen::factory()->create([
                'status' => $status,
                'assigned_user_id' => User::factory()->create()->id,
                'target_date' => now()->addDays(5),
            ]);

            try {
                $this->action->execute($kaizen, $opexUser);
                $this->fail("Should not be able to start kaizen with status: {$status->value}");
            } catch (\Exception $e) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_stale_start_does_not_create_second_history_record()
    {
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::IN_PROGRESS, // Already started!
            'assigned_user_id' => User::factory()->create()->id,
            'target_date' => now()->addDays(5),
            'started_at' => now()->subDay(),
        ]);
        $opexUser = User::factory()->create(['role' => UserRole::OPEX_SPECIALIST]);

        $this->expectException(\Exception::class);

        $this->action->execute($kaizen, $opexUser);
    }
}
