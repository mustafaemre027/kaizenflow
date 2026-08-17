<?php

namespace Tests\Feature\Actions;

use App\Actions\Kaizens\SubmitKaizen;
use App\Enums\KaizenStatus;
use App\Enums\UserRole;
use App\Exceptions\InvalidKaizenTransition;
use App\Models\Kaizen;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SubmitKaizenTest extends TestCase
{
    use RefreshDatabase;

    private SubmitKaizen $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = app(SubmitKaizen::class);
    }

    public function test_active_creator_employee_can_submit_draft_kaizen(): void
    {
        $creator = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $creator->id,
            'status' => KaizenStatus::DRAFT,
            'submitted_at' => null,
        ]);

        $reason = '   This is a reason   ';
        $result = $this->action->execute($creator, $kaizen, $reason);

        $this->assertInstanceOf(Kaizen::class, $result);
        $this->assertEquals(KaizenStatus::SUBMITTED, $result->status);
        $this->assertNotNull($result->submitted_at);

        $this->assertDatabaseHas('kaizens', [
            'id' => $kaizen->id,
            'status' => KaizenStatus::SUBMITTED->value,
        ]);

        $this->assertDatabaseHas('kaizen_status_histories', [
            'kaizen_id' => $kaizen->id,
            'actor_user_id' => $creator->id,
            'transition_code' => 'TR-001',
            'from_status' => KaizenStatus::DRAFT->value,
            'to_status' => KaizenStatus::SUBMITTED->value,
            'reason' => 'This is a reason', // trimmed
            'metadata' => null,
        ]);
    }

    public function test_active_creator_employee_can_resubmit_revision_requested_kaizen(): void
    {
        $creator = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $creator->id,
            'status' => KaizenStatus::REVISION_REQUESTED,
        ]);

        $result = $this->action->execute($creator, $kaizen, '');

        $this->assertEquals(KaizenStatus::SUBMITTED, $result->status);

        $this->assertDatabaseHas('kaizen_status_histories', [
            'kaizen_id' => $kaizen->id,
            'actor_user_id' => $creator->id,
            'transition_code' => 'TR-002',
            'from_status' => KaizenStatus::REVISION_REQUESTED->value,
            'to_status' => KaizenStatus::SUBMITTED->value,
            'reason' => null, // empty string is nullified
        ]);
    }

    public function test_reason_exceeding_2000_chars_is_rejected(): void
    {
        $creator = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $creator->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        $reason = Str::random(2001);

        $this->expectException(ValidationException::class);
        $this->action->execute($creator, $kaizen, $reason);
    }

    public function test_other_user_cannot_submit_kaizen(): void
    {
        $creator = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $otherUser = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $creator->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Only the creator can submit this Kaizen.');

        $this->action->execute($otherUser, $kaizen);
    }

    public function test_inactive_user_cannot_submit_kaizen(): void
    {
        $creator = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'is_active' => false,
        ]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $creator->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Inactive users cannot perform this action.');

        $this->action->execute($creator, $kaizen);
    }

    public function test_user_with_wrong_role_cannot_submit_kaizen(): void
    {
        // Even if the creator has an OPEX role (though workflow dictates EMPLOYEE creates)
        $creator = User::factory()->create(['role' => UserRole::OPEX_SPECIALIST]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $creator->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Your role is not authorized to submit this Kaizen.');

        $this->action->execute($creator, $kaizen);
    }

    public function test_submitted_kaizen_cannot_be_resubmitted_again(): void
    {
        $creator = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $creator->id,
            'status' => KaizenStatus::SUBMITTED,
        ]);

        $this->expectException(InvalidKaizenTransition::class);

        $this->action->execute($creator, $kaizen);
    }

    public function test_kaizen_in_invalid_states_cannot_be_submitted(): void
    {
        $creator = User::factory()->create(['role' => UserRole::EMPLOYEE]);

        $invalidStatuses = [
            KaizenStatus::MANAGER_REVIEW,
            KaizenStatus::APPROVED,
            KaizenStatus::IN_PROGRESS,
            KaizenStatus::COMPLETED,
            KaizenStatus::REJECTED,
        ];

        foreach ($invalidStatuses as $status) {
            $kaizen = Kaizen::factory()->create([
                'creator_user_id' => $creator->id,
                'status' => $status,
            ]);

            try {
                $this->action->execute($creator, $kaizen);
                $this->fail("Expected InvalidKaizenTransition for status {$status->value}");
            } catch (InvalidKaizenTransition $e) {
                $this->assertDatabaseHas('kaizens', [
                    'id' => $kaizen->id,
                    'status' => $status->value,
                ]);
            }
        }
    }

    public function test_failed_attempt_does_not_create_history_record_or_change_status(): void
    {
        $creator = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $creator->id,
            'status' => KaizenStatus::MANAGER_REVIEW,
        ]);

        try {
            $this->action->execute($creator, $kaizen);
        } catch (InvalidKaizenTransition $e) {
            // Expected
        }

        $this->assertDatabaseCount('kaizen_status_histories', 0);
        $this->assertEquals(KaizenStatus::MANAGER_REVIEW, $kaizen->refresh()->status);
    }

    public function test_it_locks_for_update_and_uses_fresh_database_state_not_stale_model(): void
    {
        $creator = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $creator->id,
            'status' => KaizenStatus::DRAFT, // Database is DRAFT
        ]);

        // Manipulate model in memory to simulate a stale model
        $staleKaizen = clone $kaizen;
        $staleKaizen->status = KaizenStatus::SUBMITTED;

        // Action should re-query DB, find DRAFT, and succeed
        $result = $this->action->execute($creator, $staleKaizen);

        $this->assertEquals(KaizenStatus::SUBMITTED, $result->status);
        $this->assertDatabaseHas('kaizen_status_histories', [
            'kaizen_id' => $kaizen->id,
            'transition_code' => 'TR-001',
        ]);
    }

    public function test_it_rejects_if_database_state_is_invalid_even_if_stale_model_is_valid(): void
    {
        $creator = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $creator->id,
            'status' => KaizenStatus::APPROVED, // Database is APPROVED
        ]);

        // Manipulate model in memory to simulate a stale model
        $staleKaizen = clone $kaizen;
        $staleKaizen->status = KaizenStatus::DRAFT;

        $this->expectException(InvalidKaizenTransition::class);
        $this->expectExceptionMessage('Invalid Kaizen transition from APPROVED to SUBMITTED.');

        // Action should re-query DB, find APPROVED, and fail
        $this->action->execute($creator, $staleKaizen);
    }
}
