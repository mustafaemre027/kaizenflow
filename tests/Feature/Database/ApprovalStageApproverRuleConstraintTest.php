<?php

namespace Tests\Feature\Database;

use App\Enums\ApprovalApproverScopeSource;
use App\Enums\UserCapability;
use App\Models\ApprovalStage;
use App\Models\ApprovalWorkflow;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApprovalStageApproverRuleConstraintTest extends TestCase
{
    use RefreshDatabase;

    private ApprovalStage $stage;

    protected function setUp(): void
    {
        parent::setUp();

        $workflow = ApprovalWorkflow::factory()->create([
            'approver_resolution_mode' => 'CAPABILITY_RULE',
        ]);

        $this->stage = ApprovalStage::factory()->create([
            'approval_workflow_id' => $workflow->id,
        ]);
    }

    public function test_it_enforces_unique_rule_per_stage()
    {
        DB::table('approval_stage_approver_rules')->insert([
            'approval_stage_id' => $this->stage->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW->value,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM->value,
            'is_active' => true,
        ]);

        $this->expectException(QueryException::class);

        DB::table('approval_stage_approver_rules')->insert([
            'approval_stage_id' => $this->stage->id,
            'capability' => UserCapability::KAIZEN_BOARD_APPROVE->value,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM->value,
            'is_active' => true,
        ]);
    }

    public function test_it_rejects_invalid_capability()
    {
        $this->expectException(QueryException::class);

        DB::table('approval_stage_approver_rules')->insert([
            'approval_stage_id' => $this->stage->id,
            'capability' => 'invalid.capability',
            'scope_source' => ApprovalApproverScopeSource::SYSTEM->value,
            'is_active' => true,
        ]);
    }

    public function test_it_rejects_invalid_scope_source()
    {
        $this->expectException(QueryException::class);

        DB::table('approval_stage_approver_rules')->insert([
            'approval_stage_id' => $this->stage->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW->value,
            'scope_source' => 'INVALID_SCOPE',
            'is_active' => true,
        ]);
    }

    public function test_it_rejects_opex_with_department_scope()
    {
        $this->expectException(QueryException::class);

        DB::table('approval_stage_approver_rules')->insert([
            'approval_stage_id' => $this->stage->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW->value,
            'scope_source' => ApprovalApproverScopeSource::KAIZEN_DEPARTMENT->value,
            'is_active' => true,
        ]);
    }

    public function test_it_rejects_department_approve_with_system_scope()
    {
        $this->expectException(QueryException::class);

        DB::table('approval_stage_approver_rules')->insert([
            'approval_stage_id' => $this->stage->id,
            'capability' => UserCapability::KAIZEN_DEPARTMENT_APPROVE->value,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM->value,
            'is_active' => true,
        ]);
    }

    public function test_it_rejects_board_approve_with_department_scope()
    {
        $this->expectException(QueryException::class);

        DB::table('approval_stage_approver_rules')->insert([
            'approval_stage_id' => $this->stage->id,
            'capability' => UserCapability::KAIZEN_BOARD_APPROVE->value,
            'scope_source' => ApprovalApproverScopeSource::KAIZEN_DEPARTMENT->value,
            'is_active' => true,
        ]);
    }

    public function test_it_accepts_valid_combinations()
    {
        DB::table('approval_stage_approver_rules')->insert([
            'approval_stage_id' => $this->stage->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW->value,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM->value,
            'is_active' => true,
        ]);

        $stage2 = ApprovalStage::factory()->create();
        DB::table('approval_stage_approver_rules')->insert([
            'approval_stage_id' => $stage2->id,
            'capability' => UserCapability::KAIZEN_DEPARTMENT_APPROVE->value,
            'scope_source' => ApprovalApproverScopeSource::KAIZEN_DEPARTMENT->value,
            'is_active' => true,
        ]);

        $stage3 = ApprovalStage::factory()->create();
        DB::table('approval_stage_approver_rules')->insert([
            'approval_stage_id' => $stage3->id,
            'capability' => UserCapability::KAIZEN_BOARD_APPROVE->value,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM->value,
            'is_active' => true,
        ]);

        $this->assertDatabaseCount('approval_stage_approver_rules', 3);
    }
}
