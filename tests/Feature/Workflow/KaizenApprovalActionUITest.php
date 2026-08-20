<?php

namespace Tests\Feature\Workflow;

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

class KaizenApprovalActionUITest extends TestCase
{
    use RefreshDatabase;

    public function test_eligible_reviewer_sees_action_panel()
    {
        $reviewer = User::factory()->create();
        $workflow = ApprovalWorkflow::factory()->create();
        $stage = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id]);

        $group = ApprovalGroup::factory()->create();
        ApprovalGroupMember::factory()->create(['approval_group_id' => $group->id, 'user_id' => $reviewer->id]);
        ApprovalStageAssignment::factory()->create(['approval_stage_id' => $stage->id, 'approval_group_id' => $group->id]);

        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::SUBMITTED]);
        KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $kaizen->id,
            'approval_workflow_id' => $workflow->id,
            'current_stage_id' => $stage->id,
        ]);

        $response = $this->actingAs($reviewer)->get(route('kaizens.show', $kaizen));

        $response->assertStatus(200);
        $response->assertSee('Değerlendirme');
        $response->assertSee('Onayla');
        $response->assertSee('Revizyon İste');
        $response->assertSee('Reddet');
    }

    public function test_ineligible_user_does_not_see_action_panel()
    {
        $user = User::factory()->create();
        $workflow = ApprovalWorkflow::factory()->create();
        $stage = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id]);

        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::SUBMITTED, 'creator_user_id' => $user->id]);
        KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $kaizen->id,
            'approval_workflow_id' => $workflow->id,
            'current_stage_id' => $stage->id,
        ]);

        $response = $this->actingAs($user)->get(route('kaizens.show', $kaizen));

        $response->assertStatus(200);
        $response->assertDontSee('<h2 class="kf-panel-title">Değerlendirme</h2>', false);
        $response->assertDontSee('data-bs-target="#approveModal"', false);
        $response->assertDontSee('data-bs-target="#revisionModal"', false);
        $response->assertDontSee('data-bs-target="#rejectModal"', false);
    }

    public function test_ineligible_when_status_is_revision_requested_does_not_see_action_panel()
    {
        $reviewer = User::factory()->create();
        $workflow = ApprovalWorkflow::factory()->create();
        $stage = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id]);

        $group = ApprovalGroup::factory()->create();
        ApprovalGroupMember::factory()->create(['approval_group_id' => $group->id, 'user_id' => $reviewer->id]);
        ApprovalStageAssignment::factory()->create(['approval_stage_id' => $stage->id, 'approval_group_id' => $group->id]);

        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::REVISION_REQUESTED]);
        KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $kaizen->id,
            'approval_workflow_id' => $workflow->id,
            'current_stage_id' => $stage->id,
        ]);

        $response = $this->actingAs($reviewer)->get(route('kaizens.show', $kaizen));

        $response->assertStatus(200);
        $response->assertDontSee('<h2 class="kf-panel-title">Değerlendirme</h2>', false);
        $response->assertDontSee('data-bs-target="#approveModal"', false);
        $response->assertDontSee('data-bs-target="#rejectModal"', false);
    }

    public function test_cannot_post_approval_action_when_status_is_revision_requested()
    {
        $reviewer = User::factory()->create();
        $workflow = ApprovalWorkflow::factory()->create();
        $stage = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id]);

        $group = ApprovalGroup::factory()->create();
        ApprovalGroupMember::factory()->create(['approval_group_id' => $group->id, 'user_id' => $reviewer->id]);
        ApprovalStageAssignment::factory()->create(['approval_stage_id' => $stage->id, 'approval_group_id' => $group->id]);

        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::REVISION_REQUESTED]);
        KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $kaizen->id,
            'approval_workflow_id' => $workflow->id,
            'current_stage_id' => $stage->id,
        ]);

        $endpoints = [
            route('kaizens.workflow.approve', $kaizen),
            route('kaizens.workflow.request-revision', $kaizen),
            route('kaizens.workflow.reject', $kaizen),
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->actingAs($reviewer)->post($endpoint, ['comment' => 'test comment']);
            $response->assertStatus(403);
        }
    }
}
