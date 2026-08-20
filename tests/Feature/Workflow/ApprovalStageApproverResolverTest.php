<?php

namespace Tests\Feature\Workflow;

use App\Enums\KaizenStatus;
use App\Models\ApprovalGroup;
use App\Models\ApprovalGroupMember;
use App\Models\ApprovalStage;
use App\Models\ApprovalStageAssignment;
use App\Models\ApprovalWorkflow;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\KaizenWorkflowInstance;
use App\Models\User;
use App\Services\Workflow\ApprovalStageApproverResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalStageApproverResolverTest extends TestCase
{
    use RefreshDatabase;

    private ApprovalStageApproverResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(ApprovalStageApproverResolver::class);
    }

    public function test_inactive_user_is_not_eligible()
    {
        $user = User::factory()->inactive()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::SUBMITTED]);

        $this->assertFalse($this->resolver->canAct($user, $kaizen));
    }

    public function test_fails_when_no_active_instance()
    {
        $user = User::factory()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::SUBMITTED]);

        $this->assertFalse($this->resolver->canAct($user, $kaizen));

        // Create a completed instance
        $workflow = ApprovalWorkflow::factory()->create();
        $stage = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id]);
        KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $kaizen->id,
            'approval_workflow_id' => $workflow->id,
            'current_stage_id' => $stage->id,
            'completed_at' => now(),
        ]);

        $this->assertFalse($this->resolver->canAct($user, $kaizen->refresh()));
    }

    public function test_eligible_when_active_global_membership()
    {
        $user = User::factory()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::SUBMITTED]);

        $workflow = ApprovalWorkflow::factory()->create();
        $stage = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id]);
        KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $kaizen->id,
            'approval_workflow_id' => $workflow->id,
            'current_stage_id' => $stage->id,
        ]);

        $group = ApprovalGroup::factory()->create();
        ApprovalGroupMember::factory()->create([
            'approval_group_id' => $group->id,
            'user_id' => $user->id,
        ]);
        ApprovalStageAssignment::factory()->create([
            'approval_stage_id' => $stage->id,
            'approval_group_id' => $group->id,
            'scope' => 'GLOBAL',
        ]);

        $this->assertTrue($this->resolver->canAct($user, $kaizen->refresh()));
    }

    public function test_not_eligible_when_group_inactive()
    {
        $user = User::factory()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::SUBMITTED]);

        $workflow = ApprovalWorkflow::factory()->create();
        $stage = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id]);
        KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $kaizen->id,
            'approval_workflow_id' => $workflow->id,
            'current_stage_id' => $stage->id,
        ]);

        $group = ApprovalGroup::factory()->create(['is_active' => false]);
        ApprovalGroupMember::factory()->create([
            'approval_group_id' => $group->id,
            'user_id' => $user->id,
        ]);
        ApprovalStageAssignment::factory()->create([
            'approval_stage_id' => $stage->id,
            'approval_group_id' => $group->id,
            'scope' => 'GLOBAL',
        ]);

        $this->assertFalse($this->resolver->canAct($user, $kaizen->refresh()));
    }

    public function test_not_eligible_when_membership_inactive()
    {
        $user = User::factory()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::SUBMITTED]);

        $workflow = ApprovalWorkflow::factory()->create();
        $stage = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id]);
        KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $kaizen->id,
            'approval_workflow_id' => $workflow->id,
            'current_stage_id' => $stage->id,
        ]);

        $group = ApprovalGroup::factory()->create();
        ApprovalGroupMember::factory()->create([
            'approval_group_id' => $group->id,
            'user_id' => $user->id,
            'is_active' => false,
        ]);
        ApprovalStageAssignment::factory()->create([
            'approval_stage_id' => $stage->id,
            'approval_group_id' => $group->id,
            'scope' => 'GLOBAL',
        ]);

        $this->assertFalse($this->resolver->canAct($user, $kaizen->refresh()));
    }

    public function test_eligible_with_matching_department_scope()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::SUBMITTED, 'department_id' => $department->id]);

        $workflow = ApprovalWorkflow::factory()->create();
        $stage = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id]);
        KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $kaizen->id,
            'approval_workflow_id' => $workflow->id,
            'current_stage_id' => $stage->id,
        ]);

        $group = ApprovalGroup::factory()->create();
        ApprovalGroupMember::factory()->create([
            'approval_group_id' => $group->id,
            'user_id' => $user->id,
        ]);
        ApprovalStageAssignment::factory()->create([
            'approval_stage_id' => $stage->id,
            'approval_group_id' => $group->id,
            'scope' => 'DEPARTMENT',
            'department_id' => $department->id,
        ]);

        $this->assertTrue($this->resolver->canAct($user, $kaizen->refresh()));
    }

    public function test_not_eligible_with_mismatching_department_scope()
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();
        $user = User::factory()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::SUBMITTED, 'department_id' => $department1->id]);

        $workflow = ApprovalWorkflow::factory()->create();
        $stage = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id]);
        KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $kaizen->id,
            'approval_workflow_id' => $workflow->id,
            'current_stage_id' => $stage->id,
        ]);

        $group = ApprovalGroup::factory()->create();
        ApprovalGroupMember::factory()->create([
            'approval_group_id' => $group->id,
            'user_id' => $user->id,
        ]);
        ApprovalStageAssignment::factory()->create([
            'approval_stage_id' => $stage->id,
            'approval_group_id' => $group->id,
            'scope' => 'DEPARTMENT',
            'department_id' => $department2->id,
        ]);

        $this->assertFalse($this->resolver->canAct($user, $kaizen->refresh()));
    }

    public function test_stage_advance_updates_eligibility()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::SUBMITTED]);

        $workflow = ApprovalWorkflow::factory()->create();
        $stage1 = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id]);
        $stage2 = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id]);

        $instance = KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $kaizen->id,
            'approval_workflow_id' => $workflow->id,
            'current_stage_id' => $stage1->id,
        ]);

        $group1 = ApprovalGroup::factory()->create();
        $group2 = ApprovalGroup::factory()->create();

        ApprovalGroupMember::factory()->create(['approval_group_id' => $group1->id, 'user_id' => $userA->id]);
        ApprovalGroupMember::factory()->create(['approval_group_id' => $group2->id, 'user_id' => $userB->id]);

        ApprovalStageAssignment::factory()->create(['approval_stage_id' => $stage1->id, 'approval_group_id' => $group1->id]);
        ApprovalStageAssignment::factory()->create(['approval_stage_id' => $stage2->id, 'approval_group_id' => $group2->id]);

        $kaizen->refresh();
        $this->assertTrue($this->resolver->canAct($userA, $kaizen));
        $this->assertFalse($this->resolver->canAct($userB, $kaizen));

        // Advance to stage 2
        $instance->update(['current_stage_id' => $stage2->id]);
        $kaizen->refresh();

        $this->assertFalse($this->resolver->canAct($userA, $kaizen));
        $this->assertTrue($this->resolver->canAct($userB, $kaizen));
    }

    public function test_not_eligible_when_status_is_revision_requested()
    {
        $user = User::factory()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::REVISION_REQUESTED]);

        $workflow = ApprovalWorkflow::factory()->create();
        $stage = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id]);
        KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $kaizen->id,
            'approval_workflow_id' => $workflow->id,
            'current_stage_id' => $stage->id,
        ]);

        $group = ApprovalGroup::factory()->create();
        ApprovalGroupMember::factory()->create(['approval_group_id' => $group->id, 'user_id' => $user->id]);
        ApprovalStageAssignment::factory()->create(['approval_stage_id' => $stage->id, 'approval_group_id' => $group->id, 'scope' => 'GLOBAL']);

        $this->assertFalse($this->resolver->canAct($user, $kaizen->refresh()));
    }

    public function test_not_eligible_when_status_is_terminal()
    {
        $user = User::factory()->create();
        $workflow = ApprovalWorkflow::factory()->create();
        $stage = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id]);
        $group = ApprovalGroup::factory()->create();
        ApprovalGroupMember::factory()->create(['approval_group_id' => $group->id, 'user_id' => $user->id]);
        ApprovalStageAssignment::factory()->create(['approval_stage_id' => $stage->id, 'approval_group_id' => $group->id, 'scope' => 'GLOBAL']);

        $statuses = [
            KaizenStatus::DRAFT,
            KaizenStatus::APPROVED,
            KaizenStatus::REJECTED,
        ];

        foreach ($statuses as $status) {
            $kaizen = Kaizen::factory()->create(['status' => $status]);
            KaizenWorkflowInstance::factory()->create([
                'kaizen_id' => $kaizen->id,
                'approval_workflow_id' => $workflow->id,
                'current_stage_id' => $stage->id,
            ]);

            $this->assertFalse($this->resolver->canAct($user, $kaizen->refresh()));
        }
    }
}
