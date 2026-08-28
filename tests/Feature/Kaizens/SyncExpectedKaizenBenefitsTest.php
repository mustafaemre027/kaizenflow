<?php

namespace Tests\Feature\Kaizens;

use App\Actions\Kaizens\SyncExpectedKaizenBenefits;
use App\Enums\KaizenStatus;
use App\Enums\UserRole;
use App\Models\BenefitType;
use App\Models\Category;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\KaizenBenefit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Blok 3 — SyncExpectedKaizenBenefits Action + HTTP integration tests.
 *
 * Scenarios covered:
 *   CREATE:      1-6
 *   UPDATE:      7-13
 *   LIFECYCLE:   14
 *   TRANSACTION: 15
 *   AUTHZ:       16
 *   UI:          17-21
 *   REGRESSION:  22
 */
class SyncExpectedKaizenBenefitsTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function actor(): User
    {
        $department = Department::factory()->create(['is_active' => true]);

        return User::factory()->create([
            'is_active' => true,
            'department_id' => $department->id,
            'role' => UserRole::EMPLOYEE,
        ]);
    }

    private function draftKaizen(?User $creator = null): Kaizen
    {
        $creator ??= $this->actor();

        return Kaizen::factory()->create([
            'creator_user_id' => $creator->id,
            'status' => KaizenStatus::DRAFT,
        ]);
    }

    private function sync(): SyncExpectedKaizenBenefits
    {
        return app(SyncExpectedKaizenBenefits::class);
    }

    // -----------------------------------------------------------------------
    // CREATE — Scenario 1: no benefits submitted
    // -----------------------------------------------------------------------

    public function test_create_1_no_benefits_succeeds(): void
    {
        $actor = $this->actor();
        $kaizen = $this->draftKaizen($actor);

        $this->sync()->execute($actor, $kaizen, []);

        $this->assertDatabaseMissing('kaizen_benefits', ['kaizen_id' => $kaizen->id]);
    }

    // -----------------------------------------------------------------------
    // CREATE — Scenario 2: one benefit
    // -----------------------------------------------------------------------

    public function test_create_2_one_benefit_persists_record(): void
    {
        $actor = $this->actor();
        $kaizen = $this->draftKaizen($actor);
        $type = BenefitType::factory()->create(['name' => 'Zaman Tasarrufu', 'unit_label' => 'saat']);

        $this->sync()->execute($actor, $kaizen, [
            ['benefit_type_id' => $type->id, 'expected_value' => '10', 'expected_note' => 'Günlük 10 saat'],
        ]);

        $this->assertDatabaseHas('kaizen_benefits', [
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $type->id,
            'expected_note' => 'Günlük 10 saat',
        ]);
        $this->assertDatabaseCount('kaizen_benefits', 1);
    }

    // -----------------------------------------------------------------------
    // CREATE — Scenario 3: multiple benefits
    // -----------------------------------------------------------------------

    public function test_create_3_multiple_benefits_persists_all(): void
    {
        $actor = $this->actor();
        $kaizen = $this->draftKaizen($actor);
        $type1 = BenefitType::factory()->create();
        $type2 = BenefitType::factory()->create();

        $this->sync()->execute($actor, $kaizen, [
            ['benefit_type_id' => $type1->id, 'expected_value' => '5',  'expected_note' => null],
            ['benefit_type_id' => $type2->id, 'expected_value' => '20', 'expected_note' => 'İkinci fayda'],
        ]);

        $this->assertDatabaseCount('kaizen_benefits', 2);
        $this->assertDatabaseHas('kaizen_benefits', ['kaizen_id' => $kaizen->id, 'benefit_type_id' => $type1->id]);
        $this->assertDatabaseHas('kaizen_benefits', ['kaizen_id' => $kaizen->id, 'benefit_type_id' => $type2->id]);
    }

    // -----------------------------------------------------------------------
    // CREATE — Scenario 4: inactive new type reject
    // -----------------------------------------------------------------------

    public function test_create_4_new_inactive_type_is_rejected(): void
    {
        $actor = $this->actor();
        $kaizen = $this->draftKaizen($actor);
        $inactive = BenefitType::factory()->inactive()->create();

        $this->expectException(ValidationException::class);

        $this->sync()->execute($actor, $kaizen, [
            ['benefit_type_id' => $inactive->id, 'expected_value' => '5', 'expected_note' => null],
        ]);
    }

    // -----------------------------------------------------------------------
    // CREATE — Scenario 5: unknown type reject
    // -----------------------------------------------------------------------

    public function test_create_5_unknown_type_id_is_rejected(): void
    {
        $actor = $this->actor();
        $kaizen = $this->draftKaizen($actor);

        $this->expectException(ValidationException::class);

        $this->sync()->execute($actor, $kaizen, [
            ['benefit_type_id' => 99999, 'expected_value' => '5', 'expected_note' => null],
        ]);
    }

    // -----------------------------------------------------------------------
    // CREATE — Scenario 6: duplicate type — last entry wins (dedup via array key)
    // -----------------------------------------------------------------------

    public function test_create_6_duplicate_type_deduped_last_wins(): void
    {
        $actor = $this->actor();
        $kaizen = $this->draftKaizen($actor);
        $type = BenefitType::factory()->create();

        $this->sync()->execute($actor, $kaizen, [
            ['benefit_type_id' => $type->id, 'expected_value' => '1', 'expected_note' => 'birinci'],
            ['benefit_type_id' => $type->id, 'expected_value' => '2', 'expected_note' => 'ikinci'],
        ]);

        $this->assertDatabaseCount('kaizen_benefits', 1);
        $this->assertDatabaseHas('kaizen_benefits', [
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $type->id,
            'expected_note' => 'ikinci',
        ]);
    }

    // -----------------------------------------------------------------------
    // UPDATE — Scenario 7: add a new benefit to existing kaizen
    // -----------------------------------------------------------------------

    public function test_update_7_adds_new_benefit_row(): void
    {
        $actor = $this->actor();
        $kaizen = $this->draftKaizen($actor);
        $type1 = BenefitType::factory()->create();
        $type2 = BenefitType::factory()->create();

        KaizenBenefit::factory()->create([
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $type1->id,
        ]);

        $this->sync()->execute($actor, $kaizen, [
            ['benefit_type_id' => $type1->id, 'expected_value' => '5',  'expected_note' => null],
            ['benefit_type_id' => $type2->id, 'expected_value' => '10', 'expected_note' => 'yeni'],
        ]);

        $this->assertDatabaseCount('kaizen_benefits', 2);
        $this->assertDatabaseHas('kaizen_benefits', ['kaizen_id' => $kaizen->id, 'benefit_type_id' => $type2->id]);
    }

    // -----------------------------------------------------------------------
    // UPDATE — Scenario 8: modify existing benefit
    // -----------------------------------------------------------------------

    public function test_update_8_modifies_existing_benefit_fields(): void
    {
        $actor = $this->actor();
        $kaizen = $this->draftKaizen($actor);
        $type = BenefitType::factory()->create();

        KaizenBenefit::factory()->withExpected('50.0000', 'eski not')->create([
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $type->id,
        ]);

        $this->sync()->execute($actor, $kaizen, [
            ['benefit_type_id' => $type->id, 'expected_value' => '75', 'expected_note' => 'yeni not'],
        ]);

        $this->assertDatabaseHas('kaizen_benefits', [
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $type->id,
            'expected_note' => 'yeni not',
        ]);
    }

    // -----------------------------------------------------------------------
    // UPDATE — Scenario 9: remove a benefit (no realized data)
    // -----------------------------------------------------------------------

    public function test_update_9_removes_benefit_when_not_in_payload(): void
    {
        $actor = $this->actor();
        $kaizen = $this->draftKaizen($actor);
        $type1 = BenefitType::factory()->create();
        $type2 = BenefitType::factory()->create();

        KaizenBenefit::factory()->withExpected('10.0000')->create([
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $type1->id,
        ]);
        KaizenBenefit::factory()->withExpected('20.0000')->create([
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $type2->id,
        ]);

        // Submit only type1 → type2 should be deleted
        $this->sync()->execute($actor, $kaizen, [
            ['benefit_type_id' => $type1->id, 'expected_value' => '10', 'expected_note' => null],
        ]);

        $this->assertDatabaseMissing('kaizen_benefits', [
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $type2->id,
        ]);
    }

    // -----------------------------------------------------------------------
    // UPDATE — Scenario 10: existing inactive linked type preserved in payload
    // -----------------------------------------------------------------------

    public function test_update_10_preserves_existing_inactive_linked_type(): void
    {
        $actor = $this->actor();
        $kaizen = $this->draftKaizen($actor);
        $active = BenefitType::factory()->create();
        $inactive = BenefitType::factory()->inactive()->create();

        KaizenBenefit::factory()->withExpected('5.0000', 'tarihsel')->create([
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $inactive->id,
        ]);

        $this->sync()->execute($actor, $kaizen, [
            ['benefit_type_id' => $active->id,   'expected_value' => '10', 'expected_note' => 'aktif'],
            ['benefit_type_id' => $inactive->id,  'expected_value' => '5',  'expected_note' => 'tarihsel'],
        ]);

        $this->assertDatabaseHas('kaizen_benefits', [
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $inactive->id,
        ]);
        $this->assertDatabaseCount('kaizen_benefits', 2);
    }

    // -----------------------------------------------------------------------
    // UPDATE — Scenario 11: new inactive injection rejected
    // -----------------------------------------------------------------------

    public function test_update_11_rejects_injecting_new_inactive_type(): void
    {
        $actor = $this->actor();
        $kaizen = $this->draftKaizen($actor);
        $inactive = BenefitType::factory()->inactive()->create();

        $this->expectException(ValidationException::class);

        $this->sync()->execute($actor, $kaizen, [
            ['benefit_type_id' => $inactive->id, 'expected_value' => '5', 'expected_note' => null],
        ]);
    }

    // -----------------------------------------------------------------------
    // UPDATE — Scenario 12: legacy expected_benefit column is NOT touched
    // -----------------------------------------------------------------------

    public function test_update_12_legacy_expected_benefit_preserved(): void
    {
        $actor = $this->actor();
        $kaizen = $this->draftKaizen($actor);
        $type = BenefitType::factory()->create();

        $kaizen->expected_benefit = 'eski metin';
        $kaizen->save();

        $this->sync()->execute($actor, $kaizen, [
            ['benefit_type_id' => $type->id, 'expected_value' => '10', 'expected_note' => null],
        ]);

        $this->assertDatabaseHas('kaizens', [
            'id' => $kaizen->id,
            'expected_benefit' => 'eski metin',
        ]);
    }

    // -----------------------------------------------------------------------
    // UPDATE — Scenario 13: NO DUAL-WRITE — null legacy stays null
    // -----------------------------------------------------------------------

    public function test_update_13_no_dual_write_null_legacy_stays_null(): void
    {
        $actor = $this->actor();
        $kaizen = $this->draftKaizen($actor);
        $type = BenefitType::factory()->create();

        // Override factory default to be null
        $kaizen->expected_benefit = null;
        $kaizen->save();

        $this->sync()->execute($actor, $kaizen, [
            ['benefit_type_id' => $type->id, 'expected_value' => '99', 'expected_note' => 'test'],
        ]);

        $this->assertNull($kaizen->fresh()->expected_benefit);
    }

    // -----------------------------------------------------------------------
    // LIFECYCLE — Scenario 14: non-editable status mutation rejected
    // -----------------------------------------------------------------------

    public function test_lifecycle_14_rejects_mutation_on_submitted_status(): void
    {
        $actor = $this->actor();
        $kaizen = $this->draftKaizen($actor);
        $type = BenefitType::factory()->create();

        $kaizen->status = KaizenStatus::SUBMITTED;
        $kaizen->save();

        $this->expectException(\DomainException::class);

        $this->sync()->execute($actor, $kaizen, [
            ['benefit_type_id' => $type->id, 'expected_value' => '5', 'expected_note' => null],
        ]);
    }

    // -----------------------------------------------------------------------
    // TRANSACTION — Scenario 15: failure rolls back within outer transaction
    // -----------------------------------------------------------------------

    public function test_transaction_15_benefit_failure_rolls_back(): void
    {
        $actor = $this->actor();
        $kaizen = $this->draftKaizen($actor);

        DB::beginTransaction();
        try {
            $type = BenefitType::factory()->create();
            $this->sync()->execute($actor, $kaizen, [
                ['benefit_type_id' => $type->id, 'expected_value' => '5', 'expected_note' => null],
            ]);

            // Now inject an invalid type inside the same transaction
            $this->sync()->execute($actor, $kaizen, [
                ['benefit_type_id' => 99999, 'expected_value' => '5', 'expected_note' => null],
            ]);

            DB::commit();
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            DB::rollBack();
        }

        $this->assertDatabaseMissing('kaizen_benefits', ['kaizen_id' => $kaizen->id]);
    }

    // -----------------------------------------------------------------------
    // AUTHORIZATION — Scenario 16: another user cannot mutate via HTTP
    // -----------------------------------------------------------------------

    public function test_authz_16_other_user_cannot_edit_kaizen(): void
    {
        $owner = $this->actor();
        $other = $this->actor();
        $kaizen = $this->draftKaizen($owner);
        $type = BenefitType::factory()->create();

        $this->actingAs($other)
            ->patch(route('kaizens.update', $kaizen), [
                'title' => $kaizen->title,
                'current_situation' => $kaizen->current_situation,
                'proposed_situation' => $kaizen->proposed_situation,
                'benefits' => [
                    ['benefit_type_id' => $type->id, 'expected_value' => '5', 'expected_note' => null],
                ],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('kaizen_benefits', ['kaizen_id' => $kaizen->id]);
    }

    // -----------------------------------------------------------------------
    // UI — Scenario 17: create view renders DB-driven active benefit types
    // -----------------------------------------------------------------------

    public function test_ui_17_create_view_renders_active_benefit_types(): void
    {
        $actor = $this->actor();
        BenefitType::factory()->create(['name' => 'Verimlilik', 'unit_label' => '%']);
        BenefitType::factory()->inactive()->create(['name' => 'Pasif Tür']);

        $response = $this->actingAs($actor)->get(route('kaizens.create'));

        $response->assertOk();
        $response->assertSee('Verimlilik');
        $response->assertSee('%');
        $response->assertDontSee('Pasif Tür');
    }

    // -----------------------------------------------------------------------
    // UI — Scenario 18: edit view prefills existing benefits
    // -----------------------------------------------------------------------

    public function test_ui_18_edit_view_prefills_existing_benefits(): void
    {
        $actor = $this->actor();
        $kaizen = $this->draftKaizen($actor);
        $type = BenefitType::factory()->create(['name' => 'Kalite İyileştirme']);

        KaizenBenefit::factory()->withExpected('42.0000', 'prefill not')->create([
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $type->id,
        ]);

        $response = $this->actingAs($actor)->get(route('kaizens.edit', $kaizen));

        $response->assertOk();
        $response->assertSee('Kalite İyileştirme');
        $response->assertSee('prefill not');
    }

    // -----------------------------------------------------------------------
    // UI — Scenario 19: edit view shows "Pasif" badge for inactive linked type
    // -----------------------------------------------------------------------

    public function test_ui_19_edit_view_shows_pasif_badge_for_inactive_linked_type(): void
    {
        $actor = $this->actor();
        $kaizen = $this->draftKaizen($actor);
        $inactive = BenefitType::factory()->inactive()->create(['name' => 'Eski Tür']);

        KaizenBenefit::factory()->withExpected('10.0000')->create([
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $inactive->id,
        ]);

        $response = $this->actingAs($actor)->get(route('kaizens.edit', $kaizen));

        $response->assertOk();
        $response->assertSee('Eski Tür');
        $response->assertSee('Pasif');
    }

    // -----------------------------------------------------------------------
    // UI — Scenario 20: create view shows empty state when no active types
    // -----------------------------------------------------------------------

    public function test_ui_20_create_view_shows_empty_state_no_active_types(): void
    {
        $actor = $this->actor();
        BenefitType::factory()->inactive()->create();

        $response = $this->actingAs($actor)->get(route('kaizens.create'));

        $response->assertOk();
        $response->assertSee('Sistemde tanımlı aktif fayda türü bulunmuyor');
    }

    // -----------------------------------------------------------------------
    // UI — Scenario 21: show view XSS-safe output in benefit note
    // -----------------------------------------------------------------------

    public function test_ui_21_show_view_escapes_xss_in_benefit_note(): void
    {
        $actor = $this->actor();
        $kaizen = $this->draftKaizen($actor);
        $type = BenefitType::factory()->create(['name' => 'Güvenlik']);

        KaizenBenefit::factory()->withExpected('1.0000', '<script>alert(1)</script>')->create([
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $type->id,
        ]);

        $response = $this->actingAs($actor)->get(route('kaizens.show', $kaizen));

        $response->assertOk();
        // The raw script tag must NOT appear unescaped in the response body
        $response->assertDontSee('<script>alert(1)</script>', false);
        // e() produces &lt; which in the response body stream appears as the literal string &lt;
        // When Laravel assertSee escapes the needle (default), it would double-encode.
        // With escape=false we search for the literal HTML-entity string in the response body.
        // The response body contains "&amp;lt;script&amp;gt;" because Blade double-encodes e() output.
        $this->assertStringContainsString('&amp;lt;script&amp;gt;', $response->getContent());
    }

    // -----------------------------------------------------------------------
    // REGRESSION — Scenario 22: benefit-less submit still works
    // -----------------------------------------------------------------------

    public function test_regression_22_create_without_benefits_succeeds(): void
    {
        $actor = $this->actor();
        $category = Category::factory()->create(['is_active' => true]);

        $response = $this->actingAs($actor)
            ->post(route('kaizens.store'), [
                'category_id' => $category->id,
                'title' => 'Benefit olmadan Kaizen testi',
                'current_situation' => 'Mevcut durum açıklaması en az 10 karakter.',
                'proposed_situation' => 'Önerilen durum açıklaması en az 10 karakter.',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('kaizens', ['title' => 'Benefit olmadan Kaizen testi']);
        $this->assertDatabaseCount('kaizen_benefits', 0);
    }
}
