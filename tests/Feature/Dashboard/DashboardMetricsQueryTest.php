<?php

namespace Tests\Feature\Dashboard;

use App\Enums\KaizenStatus;
use App\Enums\UserCapability;
use App\Models\BenefitType;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\KaizenBenefit;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use App\Queries\DashboardMetricsQuery;
use App\Services\Kaizens\VisibleKaizensQuery;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricsQueryTest extends TestCase
{
    use RefreshDatabase;

    private User $globalUser;

    private User $limitedUser;

    private DashboardMetricsQuery $query;

    protected function setUp(): void
    {
        parent::setUp();

        $this->query = new DashboardMetricsQuery(new VisibleKaizensQuery);

        // Global User (OPEX Review)
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

        // Limited User
        $this->limitedUser = User::factory()->create(['is_active' => true]);
        UserSystemCapabilityGrant::create([
            'user_id' => $this->limitedUser->id,
            'capability' => UserCapability::ORGANIZATION_VIEW,
            'is_active' => true,
        ]);
    }

    public function test_metrics_respect_actor_visibility_scope()
    {
        // 1 Kaizen for limited user
        Kaizen::factory()->create([
            'creator_user_id' => $this->limitedUser->id,
            'status' => KaizenStatus::SUBMITTED,
        ]);

        // 1 Kaizen for some other user
        Kaizen::factory()->create([
            'creator_user_id' => User::factory()->create()->id,
            'status' => KaizenStatus::SUBMITTED,
        ]);

        $globalMetrics = $this->query->execute($this->globalUser, []);
        $limitedMetrics = $this->query->execute($this->limitedUser, []);

        $this->assertEquals(2, $globalMetrics['total_kaizens']);
        $this->assertEquals(1, $limitedMetrics['total_kaizens']);
    }

    public function test_overdue_and_in_process_logic()
    {
        // DRAFT (not in process, not overdue)
        Kaizen::factory()->create(['creator_user_id' => $this->globalUser->id, 'status' => KaizenStatus::DRAFT, 'target_date' => Carbon::yesterday()]);

        // SUBMITTED (in process, not overdue because target_date is tomorrow)
        Kaizen::factory()->create(['creator_user_id' => $this->globalUser->id, 'status' => KaizenStatus::SUBMITTED, 'target_date' => Carbon::tomorrow()]);

        // IN_PROGRESS OVERDUE (in process, overdue because target_date is yesterday)
        Kaizen::factory()->create(['creator_user_id' => $this->globalUser->id, 'status' => KaizenStatus::IN_PROGRESS, 'target_date' => Carbon::yesterday()]);

        // COMPLETED (completed, not in process, not overdue even if target_date was yesterday)
        Kaizen::factory()->create(['creator_user_id' => $this->globalUser->id, 'status' => KaizenStatus::COMPLETED, 'target_date' => Carbon::yesterday()]);

        // REJECTED (terminal, not in process, not overdue)
        Kaizen::factory()->create(['creator_user_id' => $this->globalUser->id, 'status' => KaizenStatus::REJECTED, 'target_date' => Carbon::yesterday()]);

        $metrics = $this->query->execute($this->globalUser, []);

        $this->assertEquals(5, $metrics['total_kaizens']);
        $this->assertEquals(2, $metrics['in_process_kaizens']); // SUBMITTED, IN_PROGRESS
        $this->assertEquals(1, $metrics['completed_kaizens']); // COMPLETED
        $this->assertEquals(1, $metrics['overdue_kaizens']); // IN_PROGRESS
    }

    public function test_structured_benefit_reporting_segregates_by_type()
    {
        $typeHour = BenefitType::factory()->create(['name' => 'Time', 'unit_label' => 'Hour', 'is_active' => true]);
        $typeCost = BenefitType::factory()->create(['name' => 'Cost', 'unit_label' => 'USD', 'is_active' => true]);

        $kaizen = Kaizen::factory()->create(['creator_user_id' => $this->globalUser->id, 'status' => KaizenStatus::COMPLETED]);

        KaizenBenefit::factory()->create([
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $typeHour->id,
            'expected_value' => 10.5,
            'realized_value' => 8.0,
        ]);

        KaizenBenefit::factory()->create([
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $typeCost->id,
            'expected_value' => 500,
            'realized_value' => 450,
        ]);

        $metrics = $this->query->execute($this->globalUser, []);

        $this->assertCount(2, $metrics['structured_benefits']);

        // Check sorting by name - Cost should be first, then Time
        $this->assertEquals('Cost', $metrics['structured_benefits'][0]['name']);
        $this->assertEquals(500, $metrics['structured_benefits'][0]['expected_total']);

        $this->assertEquals('Time', $metrics['structured_benefits'][1]['name']);
        $this->assertEquals(10.5, $metrics['structured_benefits'][1]['expected_total']);
    }

    public function test_filter_application()
    {
        $dept1 = Department::factory()->create();
        $dept2 = Department::factory()->create();

        Kaizen::factory()->create(['creator_user_id' => $this->globalUser->id, 'department_id' => $dept1->id]);
        Kaizen::factory()->create(['creator_user_id' => $this->globalUser->id, 'department_id' => $dept2->id]);

        $metrics = $this->query->execute($this->globalUser, ['department_id' => $dept1->id]);

        $this->assertEquals(1, $metrics['total_kaizens']);
        $this->assertEquals($dept1->id, $metrics['department_breakdown'][0]['department_id']);
    }
}
