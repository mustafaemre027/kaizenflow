<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\KaizenStatus;
use App\Models\ApprovalGroup;
use App\Models\ApprovalGroupMember;
use App\Models\ApprovalStage;
use App\Models\ApprovalStageAssignment;
use App\Models\ApprovalWorkflow;
use App\Models\Kaizen;
use App\Models\KaizenWorkflowInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_authentication()
    {
        $response = $this->get(route('approvals.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_displays_pending_approvals_for_eligible_user()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $workflow = ApprovalWorkflow::factory()->create();
        $stage = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id]);
        $group = ApprovalGroup::factory()->create();
        ApprovalGroupMember::factory()->create(['approval_group_id' => $group->id, 'user_id' => $user->id]);
        ApprovalStageAssignment::factory()->create(['approval_stage_id' => $stage->id, 'approval_group_id' => $group->id]);

        $kaizen1 = Kaizen::factory()->create(['status' => KaizenStatus::SUBMITTED]);
        KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $kaizen1->id,
            'approval_workflow_id' => $workflow->id,
            'current_stage_id' => $stage->id,
        ]);

        $kaizen2 = Kaizen::factory()->create(['status' => KaizenStatus::SUBMITTED]);
        KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $kaizen2->id,
            'approval_workflow_id' => $workflow->id,
            'current_stage_id' => $stage->id,
        ]);

        $response = $this->get(route('approvals.index'));

        $response->assertStatus(200);
        $response->assertSee($kaizen1->code);
        $response->assertSee($kaizen2->code);
    }

    public function test_does_not_display_kaizens_for_ineligible_user()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $workflow = ApprovalWorkflow::factory()->create();
        $stage = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id]);

        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::SUBMITTED]);
        KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $kaizen->id,
            'approval_workflow_id' => $workflow->id,
            'current_stage_id' => $stage->id,
        ]);

        $response = $this->get(route('approvals.index'));

        $response->assertStatus(200);
        $response->assertDontSee($kaizen->code);
    }

    public function test_eligible_reviewer_can_view_kaizen_detail()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $workflow = ApprovalWorkflow::factory()->create();
        $stage = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id]);
        $group = ApprovalGroup::factory()->create();
        ApprovalGroupMember::factory()->create(['approval_group_id' => $group->id, 'user_id' => $user->id]);
        ApprovalStageAssignment::factory()->create(['approval_stage_id' => $stage->id, 'approval_group_id' => $group->id]);

        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::SUBMITTED]);
        KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $kaizen->id,
            'approval_workflow_id' => $workflow->id,
            'current_stage_id' => $stage->id,
        ]);

        $response = $this->get(route('kaizens.show', $kaizen));
        $response->assertStatus(200);
    }

    public function test_does_not_display_pending_approvals_for_revision_requested_kaizen()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $workflow = ApprovalWorkflow::factory()->create();
        $stage = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id]);
        $group = ApprovalGroup::factory()->create();
        ApprovalGroupMember::factory()->create(['approval_group_id' => $group->id, 'user_id' => $user->id]);
        ApprovalStageAssignment::factory()->create(['approval_stage_id' => $stage->id, 'approval_group_id' => $group->id]);

        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::REVISION_REQUESTED]);
        KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $kaizen->id,
            'approval_workflow_id' => $workflow->id,
            'current_stage_id' => $stage->id,
        ]);

        $response = $this->get(route('approvals.index'));

        $response->assertStatus(200);
        $response->assertDontSee($kaizen->code);
    }
}
