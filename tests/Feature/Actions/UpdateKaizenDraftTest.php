<?php

namespace Tests\Feature\Actions;

use App\Actions\Kaizens\UpdateKaizenDraft;
use App\Enums\KaizenPriority;
use App\Enums\KaizenStatus;
use App\Models\Category;
use App\Models\Kaizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateKaizenDraftTest extends TestCase
{
    use RefreshDatabase;

    private UpdateKaizenDraft $action;

    private User $creator;

    private Kaizen $kaizen;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = $this->app->make(UpdateKaizenDraft::class);

        $this->creator = User::factory()->create(['is_active' => true]);
        $this->kaizen = Kaizen::factory()->withStatus(KaizenStatus::DRAFT)->create([
            'creator_user_id' => $this->creator->id,
            'title' => 'Old Title',
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_creator_can_update_draft(): void
    {
        $updatedKaizen = $this->action->execute($this->creator, $this->kaizen, [
            'title' => 'New Title',
        ]);

        $this->assertEquals('New Title', $updatedKaizen->title);
        $this->assertDatabaseHas('kaizens', [
            'id' => $this->kaizen->id,
            'title' => 'New Title',
        ]);
    }

    public function test_creator_can_update_revision_requested(): void
    {
        $this->kaizen->status = KaizenStatus::REVISION_REQUESTED;
        $this->kaizen->save();

        $updatedKaizen = $this->action->execute($this->creator, $this->kaizen, [
            'title' => 'New Title',
        ]);

        $this->assertEquals('New Title', $updatedKaizen->title);
    }

    public function test_non_creator_cannot_update(): void
    {
        $otherUser = User::factory()->create(['is_active' => true]);

        $this->expectException(ValidationException::class);
        $this->action->execute($otherUser, $this->kaizen, [
            'title' => 'New Title',
        ]);
    }

    public function test_inactive_creator_cannot_update(): void
    {
        $this->creator->is_active = false;
        $this->creator->save();

        $this->expectException(ValidationException::class);
        $this->action->execute($this->creator, $this->kaizen, [
            'title' => 'New Title',
        ]);
    }

    public function test_cannot_update_other_statuses(): void
    {
        $statuses = [
            KaizenStatus::APPROVED,
            KaizenStatus::IN_PROGRESS,
            KaizenStatus::COMPLETED,
            KaizenStatus::REJECTED,
            KaizenStatus::SUBMITTED,
            KaizenStatus::MANAGER_REVIEW,
        ];

        foreach ($statuses as $status) {
            $this->kaizen->status = $status;
            $this->kaizen->save();

            try {
                $this->action->execute($this->creator, $this->kaizen, ['title' => 'New Title']);
                $this->fail("Should not be able to update Kaizen with status {$status->value}");
            } catch (ValidationException $e) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_can_change_to_active_category(): void
    {
        $newCategory = Category::factory()->create(['is_active' => true]);

        $updatedKaizen = $this->action->execute($this->creator, $this->kaizen, [
            'category_id' => $newCategory->id,
        ]);

        $this->assertEquals($newCategory->id, $updatedKaizen->category_id);
    }

    public function test_rejects_inactive_category(): void
    {
        $newCategory = Category::factory()->create(['is_active' => false]);

        $this->expectException(ValidationException::class);
        $this->action->execute($this->creator, $this->kaizen, [
            'category_id' => $newCategory->id,
        ]);
    }

    public function test_rejects_non_existent_category(): void
    {
        $this->expectException(ValidationException::class);
        $this->action->execute($this->creator, $this->kaizen, [
            'category_id' => 9999,
        ]);
    }

    public function test_sensitive_fields_are_ignored(): void
    {
        $otherUser = User::factory()->create();

        $originalCode = $this->kaizen->code;
        $originalDepartment = $this->kaizen->department_id;

        $updatedKaizen = $this->action->execute($this->creator, $this->kaizen, [
            'title' => 'Valid Update',
            'code' => 'HACKED',
            'creator_user_id' => $otherUser->id,
            'department_id' => 99,
            'assigned_user_id' => $otherUser->id,
            'status' => KaizenStatus::APPROVED->value,
        ]);

        $this->assertEquals('Valid Update', $updatedKaizen->title);
        $this->assertEquals($originalCode, $updatedKaizen->code);
        $this->assertEquals($this->creator->id, $updatedKaizen->creator_user_id);
        $this->assertEquals($originalDepartment, $updatedKaizen->department_id);
        $this->assertNull($updatedKaizen->assigned_user_id);
        $this->assertEquals(KaizenStatus::DRAFT, $updatedKaizen->status);
    }

    public function test_priority_and_target_date_can_be_nulled(): void
    {
        $this->kaizen->priority = KaizenPriority::HIGH;
        $this->kaizen->target_date = now()->addDays(5);
        $this->kaizen->save();

        $updatedKaizen = $this->action->execute($this->creator, $this->kaizen, [
            'priority' => null,
            'target_date' => null,
        ]);

        $this->assertNull($updatedKaizen->priority);
        $this->assertNull($updatedKaizen->target_date);
    }

    public function test_rejects_empty_payload(): void
    {
        $this->expectException(ValidationException::class);
        $this->action->execute($this->creator, $this->kaizen, []);
    }

    public function test_multiple_fields_are_updated_atomically(): void
    {
        $updatedKaizen = $this->action->execute($this->creator, $this->kaizen, [
            'title' => 'T2',
            'current_situation' => 'C2',
            'proposed_situation' => 'P2',
        ]);

        $this->assertEquals('T2', $updatedKaizen->title);
        $this->assertEquals('C2', $updatedKaizen->current_situation);
        $this->assertEquals('P2', $updatedKaizen->proposed_situation);
    }

    public function test_stale_object_in_memory_is_rejected_if_db_status_changed(): void
    {
        // $this->kaizen is DRAFT in memory.

        // Another process updates the DB to SUBMITTED
        Kaizen::where('id', $this->kaizen->id)->update(['status' => KaizenStatus::SUBMITTED->value]);

        // We try to update using the stale DRAFT object
        $this->expectException(ValidationException::class);

        // Action will lockForUpdate() and read the fresh DB record which is SUBMITTED, and reject it.
        $this->action->execute($this->creator, $this->kaizen, [
            'title' => 'New Title',
        ]);
    }

    public function test_it_rolls_back_transaction_on_saving_failure(): void
    {
        $initialTitle = $this->kaizen->title;
        $initialSituation = $this->kaizen->current_situation;

        // Geçici bir global listener ekliyoruz. Bu, DB kayıt aşamasında kontrollü hata üretecek.
        Kaizen::flushEventListeners(); // Önceki eventleri temizle (varsa) - Test amaçlı
        Kaizen::updating(function ($model) {
            throw new \RuntimeException('Simulated DB error during update');
        });

        try {
            $this->action->execute($this->creator, $this->kaizen, [
                'title' => 'Hacked Title',
                'current_situation' => 'Hacked Situation',
            ]);
            $this->fail('Exception should have been thrown.');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Simulated DB error during update', $e->getMessage());
        } finally {
            // Sonraki testlere sızmaması için event listener'ları geri yükle
            Kaizen::flushEventListeners();
            // Temel observer'ların geri yüklenmesi gerekiyorsa burada yapılabilir.
            // Fakat Kaizen modeli için henüz observer tanımlanmadı, bu yüzden flush yeterli.
        }

        // Veritabanındaki eski değerin korunduğunu doğrula
        $this->assertDatabaseHas('kaizens', [
            'id' => $this->kaizen->id,
            'title' => $initialTitle,
            'current_situation' => $initialSituation,
        ]);
    }
}
