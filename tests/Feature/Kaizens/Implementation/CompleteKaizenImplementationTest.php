<?php

namespace Tests\Feature\Kaizens\Implementation;

use App\Actions\Kaizens\CompleteKaizenImplementation;
use App\Enums\KaizenStatus;
use App\Enums\UserRole;
use App\Models\Kaizen;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompleteKaizenImplementationTest extends TestCase
{
    use RefreshDatabase;

    private CompleteKaizenImplementation $action;

    protected function setUp(): void
    {
        parent::setUp();
        // Will create this class shortly
        $this->action = app(CompleteKaizenImplementation::class);
    }

    public function test_authorized_user_can_complete_in_progress_kaizen_with_valid_actual_result()
    {
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::IN_PROGRESS,
            'assigned_user_id' => User::factory()->create()->id,
            'target_date' => now()->addDays(5),
            'started_at' => now()->subDays(2),
        ]);
        $opexUser = User::factory()->create(['role' => UserRole::OPEX_SPECIALIST, 'is_active' => true]);

        $actualResult = 'Successfully implemented the new process.';

        $kaizen = $this->action->execute($kaizen, $opexUser, $actualResult);

        $this->assertEquals(KaizenStatus::COMPLETED, $kaizen->status);
        $this->assertNotNull($kaizen->completed_at);
        $this->assertEquals($actualResult, $kaizen->actual_result);

        // Exact one lifecycle history should be generated
        $this->assertDatabaseHas('kaizen_status_histories', [
            'kaizen_id' => $kaizen->id,
            'to_status' => KaizenStatus::COMPLETED->value,
            'actor_user_id' => $opexUser->id,
        ]);
    }

    public function test_unauthorized_user_cannot_complete()
    {
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::IN_PROGRESS,
            'assigned_user_id' => User::factory()->create()->id,
            'target_date' => now()->addDays(5),
            'started_at' => now()->subDays(2),
        ]);
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE, 'is_active' => true]);

        $this->expectException(AuthorizationException::class);

        $this->action->execute($kaizen, $employee, 'Result text');
    }

    public function test_assignee_cannot_complete_just_because_they_are_assigned()
    {
        $assignee = User::factory()->create(['role' => UserRole::EMPLOYEE, 'is_active' => true]);
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::IN_PROGRESS,
            'assigned_user_id' => $assignee->id,
            'target_date' => now()->addDays(5),
            'started_at' => now()->subDays(2),
        ]);

        $this->expectException(AuthorizationException::class);

        $this->action->execute($kaizen, $assignee, 'Result text');
    }

    public function test_cannot_complete_without_actual_result()
    {
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::IN_PROGRESS,
            'assigned_user_id' => User::factory()->create()->id,
            'target_date' => now()->addDays(5),
            'started_at' => now()->subDays(2),
        ]);
        $opexUser = User::factory()->create(['role' => UserRole::OPEX_SPECIALIST]);

        $invalidResults = [null, '', '   '];

        foreach ($invalidResults as $result) {
            try {
                $this->action->execute($kaizen, $opexUser, $result);
                $this->fail('Should not be able to complete with invalid actual_result');
            } catch (\InvalidArgumentException $e) {
                $this->assertEquals('Kaizen actual result is required and cannot be empty.', $e->getMessage());
            }
        }
    }

    public function test_cannot_complete_if_not_in_progress()
    {
        $invalidStatuses = collect(KaizenStatus::cases())
            ->reject(fn ($s) => $s === KaizenStatus::IN_PROGRESS);

        $opexUser = User::factory()->create(['role' => UserRole::OPEX_SPECIALIST]);

        foreach ($invalidStatuses as $status) {
            $kaizen = Kaizen::factory()->create([
                'status' => $status,
                'assigned_user_id' => User::factory()->create()->id,
                'target_date' => now()->addDays(5),
            ]);

            try {
                $this->action->execute($kaizen, $opexUser, 'Valid result');
                $this->fail("Should not be able to complete kaizen with status: {$status->value}");
            } catch (\Exception $e) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_second_completion_is_rejected()
    {
        $kaizen = Kaizen::factory()->create([
            'status' => KaizenStatus::COMPLETED,
            'assigned_user_id' => User::factory()->create()->id,
            'target_date' => now()->addDays(5),
            'started_at' => now()->subDays(2),
            'completed_at' => now()->subDays(1),
            'actual_result' => 'First completion',
        ]);
        $opexUser = User::factory()->create(['role' => UserRole::OPEX_SPECIALIST]);

        $this->expectException(\Exception::class);

        $this->action->execute($kaizen, $opexUser, 'Second completion attempt');
    }
}
