<?php

namespace Tests\Feature\Models;

use App\Enums\KaizenStatus;
use App\Models\Kaizen;
use App\Models\KaizenStatusHistory;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KaizenStatusHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_has_expected_columns(): void
    {
        $this->assertTrue(
            Schema::hasColumns('kaizen_status_histories', [
                'id',
                'kaizen_id',
                'actor_user_id',
                'transition_code',
                'from_status',
                'to_status',
                'reason',
                'metadata',
                'created_at',
            ])
        );

        $this->assertFalse(Schema::hasColumn('kaizen_status_histories', 'updated_at'));
        $this->assertFalse(Schema::hasColumn('kaizen_status_histories', 'deleted_at'));
    }

    public function test_it_can_be_created_using_factory(): void
    {
        $history = KaizenStatusHistory::factory()->create();

        $this->assertDatabaseCount('kaizen_status_histories', 1);
        $this->assertInstanceOf(KaizenStatusHistory::class, $history);
    }

    public function test_it_belongs_to_a_kaizen(): void
    {
        $history = KaizenStatusHistory::factory()->create();

        $this->assertInstanceOf(Kaizen::class, $history->kaizen);
        $this->assertEquals($history->kaizen_id, $history->kaizen->id);
    }

    public function test_it_belongs_to_an_actor_user(): void
    {
        $history = KaizenStatusHistory::factory()->create();

        $this->assertInstanceOf(User::class, $history->actor);
        $this->assertEquals($history->actor_user_id, $history->actor->id);
    }

    public function test_kaizen_has_many_status_histories(): void
    {
        $kaizen = Kaizen::factory()->create();
        $history = KaizenStatusHistory::factory()->create(['kaizen_id' => $kaizen->id]);

        $this->assertTrue($kaizen->statusHistories->contains($history));
        $this->assertCount(1, $kaizen->statusHistories);
    }

    public function test_user_has_many_kaizen_status_histories(): void
    {
        $user = User::factory()->create();
        $history = KaizenStatusHistory::factory()->create(['actor_user_id' => $user->id]);

        $this->assertTrue($user->kaizenStatusHistories->contains($history));
        $this->assertCount(1, $user->kaizenStatusHistories);
    }

    public function test_metadata_is_cast_to_array(): void
    {
        $history = KaizenStatusHistory::factory()->create([
            'metadata' => ['key' => 'value'],
        ]);

        $this->assertIsArray($history->metadata);
        $this->assertEquals('value', $history->metadata['key']);
    }

    public function test_created_at_is_immutable_datetime(): void
    {
        $history = KaizenStatusHistory::factory()->create();

        $this->assertInstanceOf(CarbonImmutable::class, $history->created_at);
    }

    public function test_it_can_be_created_without_updated_at_column(): void
    {
        $history = KaizenStatusHistory::create([
            'kaizen_id' => Kaizen::factory()->create()->id,
            'actor_user_id' => User::factory()->create()->id,
            'transition_code' => 'TEST_TRANSITION',
            'from_status' => KaizenStatus::DRAFT->value,
            'to_status' => KaizenStatus::SUBMITTED->value,
            'reason' => 'Test reason',
        ]);

        $this->assertDatabaseHas('kaizen_status_histories', [
            'id' => $history->id,
            'transition_code' => 'TEST_TRANSITION',
        ]);

        $this->assertNull(KaizenStatusHistory::UPDATED_AT);
    }

    public function test_it_can_use_canonical_statuses(): void
    {
        $validStatuses = [
            KaizenStatus::DRAFT->value,
            KaizenStatus::SUBMITTED->value,
            KaizenStatus::REVISION_REQUESTED->value,
            KaizenStatus::MANAGER_REVIEW->value,
            KaizenStatus::APPROVED->value,
            KaizenStatus::IN_PROGRESS->value,
            KaizenStatus::COMPLETED->value,
            KaizenStatus::REJECTED->value,
        ];

        foreach ($validStatuses as $status) {
            $history = KaizenStatusHistory::factory()->create([
                'from_status' => $status,
                'to_status' => $status,
            ]);

            $this->assertEquals($status, $history->from_status);
            $this->assertEquals($status, $history->to_status);
        }
    }

    public function test_opex_review_is_not_a_valid_status(): void
    {
        $cases = collect(KaizenStatus::cases())->map(fn ($case) => $case->value)->toArray();

        $this->assertNotContains('OPEX_REVIEW', $cases);
    }

    public function test_foreign_keys_are_stored_correctly_when_records_exist(): void
    {
        $kaizen = Kaizen::factory()->create();
        $user = User::factory()->create();

        $history = KaizenStatusHistory::factory()->create([
            'kaizen_id' => $kaizen->id,
            'actor_user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('kaizen_status_histories', [
            'id' => $history->id,
            'kaizen_id' => $kaizen->id,
            'actor_user_id' => $user->id,
        ]);
    }
}
