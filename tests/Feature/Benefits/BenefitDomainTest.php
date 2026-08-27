<?php

namespace Tests\Feature\Benefits;

use App\Models\BenefitType;
use App\Models\Kaizen;
use App\Models\KaizenBenefit;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BenefitDomainTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================
    // A. benefit_types schema
    // =========================================================

    public function test_benefit_types_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('benefit_types'));
    }

    public function test_benefit_type_name_is_required(): void
    {
        $this->expectException(QueryException::class);

        BenefitType::create([
            'name' => null,
            'is_active' => true,
        ]);
    }

    public function test_benefit_type_name_must_be_unique(): void
    {
        BenefitType::factory()->create(['name' => 'Zaman']);

        $this->expectException(QueryException::class);

        BenefitType::factory()->create(['name' => 'Zaman']);
    }

    public function test_benefit_type_unit_label_is_nullable(): void
    {
        $type = BenefitType::factory()->create(['unit_label' => null]);

        $this->assertNull($type->unit_label);
        $this->assertDatabaseHas('benefit_types', ['id' => $type->id, 'unit_label' => null]);
    }

    public function test_benefit_type_is_active_defaults_to_true(): void
    {
        $type = BenefitType::create(['name' => 'Kalite', 'unit_label' => null]);
        $type = $type->fresh();

        $this->assertTrue($type->is_active);
        $this->assertDatabaseHas('benefit_types', ['id' => $type->id, 'is_active' => 1]);
    }

    // =========================================================
    // B. kaizen_benefits schema
    // =========================================================

    public function test_kaizen_benefits_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('kaizen_benefits'));
    }

    public function test_kaizen_benefit_expected_value_is_nullable(): void
    {
        $benefit = KaizenBenefit::factory()->create(['expected_value' => null]);

        $this->assertNull($benefit->expected_value);
    }

    public function test_kaizen_benefit_expected_note_is_nullable(): void
    {
        $benefit = KaizenBenefit::factory()->create(['expected_note' => null]);

        $this->assertNull($benefit->expected_note);
    }

    public function test_kaizen_benefit_realized_value_is_nullable(): void
    {
        $benefit = KaizenBenefit::factory()->create(['realized_value' => null]);

        $this->assertNull($benefit->realized_value);
    }

    public function test_kaizen_benefit_realized_note_is_nullable(): void
    {
        $benefit = KaizenBenefit::factory()->create(['realized_note' => null]);

        $this->assertNull($benefit->realized_note);
    }

    public function test_kaizen_benefit_enforces_unique_pair(): void
    {
        $kaizen = Kaizen::factory()->create();
        $type = BenefitType::factory()->create();

        KaizenBenefit::factory()->create([
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $type->id,
        ]);

        $this->expectException(QueryException::class);

        KaizenBenefit::factory()->create([
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $type->id,
        ]);
    }

    public function test_same_kaizen_can_have_different_benefit_types(): void
    {
        $kaizen = Kaizen::factory()->create();
        $typeA = BenefitType::factory()->create(['name' => 'Zaman']);
        $typeB = BenefitType::factory()->create(['name' => 'Maliyet']);

        $b1 = KaizenBenefit::factory()->create(['kaizen_id' => $kaizen->id, 'benefit_type_id' => $typeA->id]);
        $b2 = KaizenBenefit::factory()->create(['kaizen_id' => $kaizen->id, 'benefit_type_id' => $typeB->id]);

        $this->assertDatabaseCount('kaizen_benefits', 2);
        $this->assertEquals($kaizen->id, $b1->kaizen_id);
        $this->assertEquals($kaizen->id, $b2->kaizen_id);
    }

    // =========================================================
    // C. Relationships
    // =========================================================

    public function test_kaizen_has_benefits_relation(): void
    {
        $kaizen = Kaizen::factory()->create();
        $type = BenefitType::factory()->create(['name' => 'Kalite']);

        KaizenBenefit::factory()->create(['kaizen_id' => $kaizen->id, 'benefit_type_id' => $type->id]);

        $this->assertCount(1, $kaizen->benefits);
        $this->assertInstanceOf(KaizenBenefit::class, $kaizen->benefits->first());
    }

    public function test_kaizen_benefit_belongs_to_kaizen(): void
    {
        $benefit = KaizenBenefit::factory()->create();

        $this->assertInstanceOf(Kaizen::class, $benefit->kaizen);
    }

    public function test_kaizen_benefit_belongs_to_benefit_type(): void
    {
        $benefit = KaizenBenefit::factory()->create();

        $this->assertInstanceOf(BenefitType::class, $benefit->benefitType);
    }

    public function test_benefit_type_has_kaizen_benefits_relation(): void
    {
        $type = BenefitType::factory()->create(['name' => 'Verimlilik']);
        $kaizen = Kaizen::factory()->create();

        KaizenBenefit::factory()->create(['kaizen_id' => $kaizen->id, 'benefit_type_id' => $type->id]);

        $this->assertCount(1, $type->kaizenBenefits);
    }

    // =========================================================
    // D. Decimal precision round-trip
    // =========================================================

    public function test_decimal_precision_survives_db_round_trip(): void
    {
        $benefit = KaizenBenefit::factory()->create([
            'expected_value' => '1234567890.1234',
            'realized_value' => '9999999.9999',
        ]);

        $fresh = KaizenBenefit::find($benefit->id);

        $this->assertEquals('1234567890.1234', $fresh->expected_value);
        $this->assertEquals('9999999.9999', $fresh->realized_value);
    }

    public function test_small_decimal_precision_is_preserved(): void
    {
        $benefit = KaizenBenefit::factory()->create([
            'expected_value' => '0.0001',
        ]);

        $fresh = KaizenBenefit::find($benefit->id);

        $this->assertEquals('0.0001', $fresh->expected_value);
    }

    // =========================================================
    // E. Duplicate protection
    // =========================================================

    public function test_db_constraint_rejects_duplicate_kaizen_benefit_pair(): void
    {
        $kaizen = Kaizen::factory()->create();
        $type = BenefitType::factory()->create();

        KaizenBenefit::create([
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $type->id,
        ]);

        $this->expectException(QueryException::class);

        // Direct DB insert bypasses application logic — DB constraint must reject this
        KaizenBenefit::create([
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $type->id,
        ]);
    }

    // =========================================================
    // F. Benefit Type destructive safety (FK RESTRICT)
    // =========================================================

    public function test_benefit_type_with_usages_cannot_be_force_deleted(): void
    {
        $type = BenefitType::factory()->create(['name' => 'Zaman']);
        $kaizen = Kaizen::factory()->create();
        KaizenBenefit::factory()->create(['kaizen_id' => $kaizen->id, 'benefit_type_id' => $type->id]);

        $this->expectException(QueryException::class);

        $type->delete();
    }

    // =========================================================
    // G. Optional domain — all combinations nullable
    // =========================================================

    public function test_expected_only_is_valid(): void
    {
        $benefit = KaizenBenefit::factory()->create([
            'expected_value' => '100.0000',
            'expected_note' => 'Beklenen tasarruf',
            'realized_value' => null,
            'realized_note' => null,
        ]);

        $this->assertDatabaseHas('kaizen_benefits', ['id' => $benefit->id, 'realized_value' => null]);
    }

    public function test_realized_only_is_valid(): void
    {
        $benefit = KaizenBenefit::factory()->create([
            'expected_value' => null,
            'expected_note' => null,
            'realized_value' => '85.5000',
            'realized_note' => 'Gerçekleşen tasarruf',
        ]);

        $this->assertDatabaseHas('kaizen_benefits', ['id' => $benefit->id, 'expected_value' => null]);
    }

    public function test_note_only_without_numeric_is_valid(): void
    {
        $benefit = KaizenBenefit::factory()->create([
            'expected_value' => null,
            'expected_note' => 'Kalitatif fayda bekleniyor',
            'realized_value' => null,
            'realized_note' => null,
        ]);

        $this->assertDatabaseHas('kaizen_benefits', ['id' => $benefit->id]);
    }

    public function test_all_nullable_values_can_be_stored(): void
    {
        $benefit = KaizenBenefit::factory()->create([
            'expected_value' => null,
            'expected_note' => null,
            'realized_value' => null,
            'realized_note' => null,
        ]);

        $this->assertDatabaseHas('kaizen_benefits', [
            'id' => $benefit->id,
            'expected_value' => null,
            'realized_value' => null,
        ]);
    }

    // =========================================================
    // H. scopeActive
    // =========================================================

    public function test_scope_active_returns_only_active_benefit_types(): void
    {
        BenefitType::factory()->create(['name' => 'Aktif Tip', 'is_active' => true]);
        BenefitType::factory()->create(['name' => 'Pasif Tip', 'is_active' => false]);

        $active = BenefitType::active()->get();

        $this->assertCount(1, $active);
        $this->assertEquals('Aktif Tip', $active->first()->name);
    }

    // =========================================================
    // I. Inactive type historical preservation
    // =========================================================

    public function test_inactive_benefit_type_does_not_destroy_existing_relations(): void
    {
        $type = BenefitType::factory()->create(['name' => 'Zaman', 'is_active' => true]);
        $kaizen = Kaizen::factory()->create();
        $benefit = KaizenBenefit::factory()->create([
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $type->id,
            'expected_value' => '50.0000',
        ]);

        // Deactivate the type
        $type->update(['is_active' => false]);

        // Existing relation must still be queryable
        $fresh = KaizenBenefit::find($benefit->id);
        $this->assertNotNull($fresh);
        $this->assertEquals('50.0000', $fresh->expected_value);
        $this->assertEquals($type->id, $fresh->benefit_type_id);
        $this->assertFalse($fresh->benefitType->is_active);
    }

    // =========================================================
    // J. Legacy columns untouched
    // =========================================================

    public function test_kaizens_table_still_has_legacy_benefit_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('kaizens', 'expected_benefit'));
        $this->assertTrue(Schema::hasColumn('kaizens', 'realized_benefit'));
    }
}
