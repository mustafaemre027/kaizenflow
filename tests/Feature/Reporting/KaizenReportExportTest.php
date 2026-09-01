<?php

namespace Tests\Feature\Reporting;

use App\Enums\KaizenStatus;
use App\Enums\UserCapability;
use App\Enums\UserRole;
use App\Models\BenefitType;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\KaizenBenefit;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KaizenReportExportTest extends TestCase
{
    use RefreshDatabase;

    private User $globalUser;

    private User $limitedUser;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->globalUser = User::factory()->create(['is_active' => true]);
        UserSystemCapabilityGrant::create([
            'user_id' => $this->globalUser->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW,
            'is_active' => true,
        ]);
        UserSystemCapabilityGrant::create([
            'user_id' => $this->globalUser->id,
            'capability' => UserCapability::ORGANIZATION_VIEW,
            'is_active' => true,
        ]);

        $this->limitedUser = User::factory()->create(['is_active' => true]);
        UserSystemCapabilityGrant::create([
            'user_id' => $this->limitedUser->id,
            'capability' => UserCapability::ORGANIZATION_VIEW,
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);
    }

    public function test_admin_without_capability_gets_403()
    {
        $response = $this->actingAs($this->adminUser)->get(route('reports.kaizens.csv'));
        $response->assertStatus(403);
    }

    public function test_export_is_streamed_with_correct_headers_and_bom()
    {
        $response = $this->actingAs($this->globalUser)->get(route('reports.kaizens.csv'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename='.'kaizen-raporu-'.now()->format('Ymd-His').'.csv');

        $content = $response->streamedContent();

        // Assert UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        // Assert Headers exist
        $this->assertStringContainsString('Kaizen Kodu', $content);
        $this->assertStringContainsString('Beklenen Değer', $content);
    }

    public function test_export_respects_visibility_scope_and_filters()
    {
        $dept1 = Department::factory()->create();
        $dept2 = Department::factory()->create();

        // 1 Kaizen for limited user in dept 1
        $kaizenA = Kaizen::factory()->create([
            'creator_user_id' => $this->limitedUser->id,
            'department_id' => $dept1->id,
            'status' => KaizenStatus::COMPLETED,
        ]);

        // 1 Kaizen for another user in dept 2 (hidden from limited user)
        $kaizenB = Kaizen::factory()->create([
            'creator_user_id' => User::factory()->create()->id,
            'department_id' => $dept2->id,
            'status' => KaizenStatus::COMPLETED,
        ]);

        // Global user sees both
        $responseGlobal = $this->actingAs($this->globalUser)->get(route('reports.kaizens.csv'));
        $contentGlobal = $responseGlobal->streamedContent();
        $this->assertStringContainsString($kaizenA->code, $contentGlobal);
        $this->assertStringContainsString($kaizenB->code, $contentGlobal);

        // Limited user sees only A
        $responseLimited = $this->actingAs($this->limitedUser)->get(route('reports.kaizens.csv'));
        $contentLimited = $responseLimited->streamedContent();
        $this->assertStringContainsString($kaizenA->code, $contentLimited);
        $this->assertStringNotContainsString($kaizenB->code, $contentLimited);

        // Global user with filter for dept 1 sees only A
        $responseFiltered = $this->actingAs($this->globalUser)->get(route('reports.kaizens.csv', ['department_id' => $dept1->id]));
        $contentFiltered = $responseFiltered->streamedContent();
        $this->assertStringContainsString($kaizenA->code, $contentFiltered);
        $this->assertStringNotContainsString($kaizenB->code, $contentFiltered);
    }

    public function test_structured_benefits_export_without_mixing_units()
    {
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $this->globalUser->id,
        ]);

        $type1 = BenefitType::factory()->create(['name' => 'Type A', 'unit_label' => 'Unit A']);
        $type2 = BenefitType::factory()->create(['name' => 'Type B', 'unit_label' => 'Unit B']);

        KaizenBenefit::factory()->create([
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $type1->id,
            'expected_value' => 100,
        ]);

        KaizenBenefit::factory()->create([
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $type2->id,
            'expected_value' => 200,
        ]);

        $response = $this->actingAs($this->globalUser)->get(route('reports.kaizens.csv'));
        $content = $response->streamedContent();

        // 1 header line + 2 data lines = 3 lines
        $lines = array_filter(explode("\n", $content), fn ($line) => trim($line) !== '');

        $this->assertCount(3, $lines);
        $this->assertStringContainsString('Type A', $lines[1]);
        $this->assertStringContainsString('100', $lines[1]);
        $this->assertStringContainsString('Type B', $lines[2]);
        $this->assertStringContainsString('200', $lines[2]);
    }
}
