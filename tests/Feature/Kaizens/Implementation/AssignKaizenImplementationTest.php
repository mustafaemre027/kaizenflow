<?php

namespace Tests\Feature\Kaizens\Implementation;

use App\Actions\Kaizens\AssignKaizenImplementation;
use App\Enums\KaizenStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\KaizenWorkflowTransition;
use App\Models\User;
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
        // Will create this class shortly
        $this->action = app(AssignKaizenImplementation::class);
    }

    public function test_authorized_user_can_assign_active_assignee_and_target_date_to_approved_kaizen()
    {
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::APPROVED]);
        $opexUser = User::factory()->create(['role' => UserRole::OPEX_SPECIALIST, 'is_active' => true]);
        $assignee = User::factory()->create(['is_active' => true]);

        $targetDate = now()->addDays(5)->format('Y-m-d');

        $kaizen = $this->action->execute($kaizen, $opexUser, $assignee->id, $targetDate);

        $this->assertEquals($assignee->id, $kaizen->assigned_user_id);
        $this->assertEquals($targetDate, $kaizen->target_date->format('Y-m-d'));

    }

    public function test_unauthorized_user_cannot_assign()
    {
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::APPROVED]);
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE, 'is_active' => true]);
        $assignee = User::factory()->create(['is_active' => true]);

        $this->expectException(AuthorizationException::class);

        $this->action->execute($kaizen, $employee, $assignee->id, now()->addDays(1)->format('Y-m-d'));
    }

    public function test_assignee_cannot_assign_themselves_or_others()
    {
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::APPROVED]);
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE, 'is_active' => true]);

        // Even if they were assigned before, they shouldn't be able to assign
        $kaizen->assigned_user_id = $employee->id;
        $kaizen->save();

        $this->expectException(AuthorizationException::class);

        $this->action->execute($kaizen, $employee, $employee->id, now()->addDays(1)->format('Y-m-d'));
    }

    public function test_historical_reviewer_cannot_assign()
    {
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::APPROVED]);
        $reviewer = User::factory()->create([
            'role' => UserRole::MANAGER,
            'department_id' => Department::factory()->create()->id,
        ]); // different dept
        $assignee = User::factory()->create();

        // Make them a historical reviewer
        KaizenWorkflowTransition::factory()->create([
            'kaizen_id' => $kaizen->id,
            'actor_user_id' => $reviewer->id,
        ]);

        $this->expectException(AuthorizationException::class);

        $this->action->execute($kaizen, $reviewer, $assignee->id, now()->addDays(1)->format('Y-m-d'));
    }

    public function test_cannot_assign_inactive_user()
    {
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::APPROVED]);
        $opexUser = User::factory()->create(['role' => UserRole::OPEX_SPECIALIST, 'is_active' => true]);
        $assignee = User::factory()->create(['is_active' => false]);

        $this->expectException(\Exception::class);

        $this->action->execute($kaizen, $opexUser, $assignee->id, now()->addDays(1)->format('Y-m-d'));
    }

    public function test_cannot_assign_if_status_is_not_approved()
    {
        $invalidStatuses = collect(KaizenStatus::cases())
            ->reject(fn ($s) => $s === KaizenStatus::APPROVED);

        $opexUser = User::factory()->create(['role' => UserRole::OPEX_SPECIALIST]);
        $assignee = User::factory()->create();

        foreach ($invalidStatuses as $status) {
            $kaizen = Kaizen::factory()->create(['status' => $status]);

            try {
                $this->action->execute($kaizen, $opexUser, $assignee->id, now()->addDays(1)->format('Y-m-d'));
                $this->fail("Should not be able to assign kaizen with status: {$status->value}");
            } catch (\Exception $e) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_past_target_date_is_rejected()
    {
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::APPROVED]);
        $opexUser = User::factory()->create(['role' => UserRole::OPEX_SPECIALIST]);
        $assignee = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        $this->action->execute($kaizen, $opexUser, $assignee->id, now()->subDay()->format('Y-m-d'));
    }

    public function test_second_assignment_does_not_silently_override()
    {
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::APPROVED,
            'assigned_user_id' => User::factory()->create()->id,
            'target_date' => now()->addDays(2),
        ]);

        $opexUser = User::factory()->create(['role' => UserRole::OPEX_SPECIALIST]);
        $assignee = User::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kaizen already has an active implementation assignment.');

        $this->action->execute($kaizen, $opexUser, $assignee->id, now()->addDays(1)->format('Y-m-d'));
    }
}
