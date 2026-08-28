<?php

namespace Tests\Feature\Kaizens;

use App\Actions\Kaizens\SyncRealizedKaizenBenefits;
use App\Enums\KaizenStatus;
use App\Enums\UserRole;
use App\Models\BenefitType;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\KaizenBenefit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SyncRealizedKaizenBenefitsTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        $department = Department::factory()->create(['is_active' => true]);

        return User::factory()->create([
            'is_active' => true,
            'department_id' => $department->id,
            'role' => UserRole::EMPLOYEE,
        ]);
    }

    private function inProgressKaizen(?User $creator = null): Kaizen
    {
        $creator ??= $this->actor();

        return Kaizen::factory()->create([
            'creator_user_id' => $creator->id,
            'status' => KaizenStatus::IN_PROGRESS,
        ]);
    }

    private function sync(): SyncRealizedKaizenBenefits
    {
        return new SyncRealizedKaizenBenefits;
    }

    public function test_empty_payload_ignores_sync(): void
    {
        $actor = $this->actor();
        $kaizen = $this->inProgressKaizen($actor);

        $this->sync()->execute($actor, $kaizen, []);

        $this->assertDatabaseCount('kaizen_benefits', 0);
    }

    public function test_existing_expected_gets_realized_value(): void
    {
        $actor = $this->actor();
        $kaizen = $this->inProgressKaizen($actor);
        $type = BenefitType::factory()->create();

        KaizenBenefit::factory()->withExpected('10.0000', 'eski not')->create([
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $type->id,
        ]);

        $this->sync()->execute($actor, $kaizen, [
            ['benefit_type_id' => $type->id, 'realized_value' => '15.5', 'realized_note' => 'yeni not'],
        ]);

        $this->assertDatabaseHas('kaizen_benefits', [
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $type->id,
            'expected_value' => '10.0000',
            'expected_note' => 'eski not',
            'realized_value' => '15.5000',
            'realized_note' => 'yeni not',
        ]);
    }

    public function test_existing_inactive_type_gets_realized_data(): void
    {
        $actor = $this->actor();
        $kaizen = $this->inProgressKaizen($actor);
        $type = BenefitType::factory()->create(['is_active' => false]);

        KaizenBenefit::factory()->withExpected('5.0000')->create([
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $type->id,
        ]);

        $this->sync()->execute($actor, $kaizen, [
            ['benefit_type_id' => $type->id, 'realized_value' => '5.0', 'realized_note' => 'success'],
        ]);

        $this->assertDatabaseHas('kaizen_benefits', [
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $type->id,
            'realized_value' => '5.0000',
            'realized_note' => 'success',
        ]);
    }

    public function test_active_unlinked_creates_realized_only_row(): void
    {
        $actor = $this->actor();
        $kaizen = $this->inProgressKaizen($actor);
        $type = BenefitType::factory()->create();

        $this->sync()->execute($actor, $kaizen, [
            ['benefit_type_id' => $type->id, 'realized_value' => '20', 'realized_note' => 'sürpriz fayda'],
        ]);

        $this->assertDatabaseHas('kaizen_benefits', [
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $type->id,
            'expected_value' => null,
            'expected_note' => null,
            'realized_value' => '20.0000',
            'realized_note' => 'sürpriz fayda',
        ]);
    }

    public function test_inactive_unlinked_is_rejected(): void
    {
        $actor = $this->actor();
        $kaizen = $this->inProgressKaizen($actor);
        $type = BenefitType::factory()->create(['is_active' => false]);

        $this->expectException(ValidationException::class);

        $this->sync()->execute($actor, $kaizen, [
            ['benefit_type_id' => $type->id, 'realized_value' => '10'],
        ]);
    }

    public function test_unknown_type_is_rejected(): void
    {
        $actor = $this->actor();
        $kaizen = $this->inProgressKaizen($actor);

        $this->expectException(ValidationException::class);

        $this->sync()->execute($actor, $kaizen, [
            ['benefit_type_id' => 9999, 'realized_value' => '10'],
        ]);
    }

    public function test_duplicate_types_are_rejected(): void
    {
        $actor = $this->actor();
        $kaizen = $this->inProgressKaizen($actor);
        $type = BenefitType::factory()->create();

        $this->expectException(ValidationException::class);

        $this->sync()->execute($actor, $kaizen, [
            ['benefit_type_id' => $type->id, 'realized_value' => '10'],
            ['benefit_type_id' => $type->id, 'realized_value' => '20'],
        ]);
    }

    public function test_blank_placeholder_is_ignored(): void
    {
        $actor = $this->actor();
        $kaizen = $this->inProgressKaizen($actor);
        $type = BenefitType::factory()->create();

        $this->sync()->execute($actor, $kaizen, [
            ['benefit_type_id' => $type->id, 'realized_value' => '', 'realized_note' => null],
        ]);

        $this->assertDatabaseCount('kaizen_benefits', 0);
    }

    public function test_note_only_realized_benefit_accepted(): void
    {
        $actor = $this->actor();
        $kaizen = $this->inProgressKaizen($actor);
        $type = BenefitType::factory()->create();

        $this->sync()->execute($actor, $kaizen, [
            ['benefit_type_id' => $type->id, 'realized_value' => '', 'realized_note' => 'just note'],
        ]);

        $this->assertDatabaseHas('kaizen_benefits', [
            'benefit_type_id' => $type->id,
            'realized_value' => null,
            'realized_note' => 'just note',
        ]);
    }

    public function test_numeric_only_realized_benefit_accepted(): void
    {
        $actor = $this->actor();
        $kaizen = $this->inProgressKaizen($actor);
        $type = BenefitType::factory()->create();

        $this->sync()->execute($actor, $kaizen, [
            ['benefit_type_id' => $type->id, 'realized_value' => '42', 'realized_note' => ''],
        ]);

        $this->assertDatabaseHas('kaizen_benefits', [
            'benefit_type_id' => $type->id,
            'realized_value' => '42.0000',
            'realized_note' => null,
        ]);
    }

    public function test_wrong_lifecycle_is_rejected(): void
    {
        $actor = $this->actor();
        $kaizen = $this->inProgressKaizen($actor);
        $kaizen->status = KaizenStatus::COMPLETED;
        $kaizen->save();
        $type = BenefitType::factory()->create();

        $this->expectException(\DomainException::class);

        $this->sync()->execute($actor, $kaizen, [
            ['benefit_type_id' => $type->id, 'realized_value' => '10'],
        ]);
    }
}
