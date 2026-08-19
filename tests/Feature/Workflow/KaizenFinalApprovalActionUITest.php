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

class KaizenFinalApprovalActionUITest extends TestCase
{
    use RefreshDatabase;

    public function test_intermediate_eligible_reviewer_page_visible_approve_trigger_is_not_post_submit_button()
    {
        $setup = $this->setupWorkflowAndUser(false);
        $response = $this->actingAs($setup['reviewer'])->get(route('kaizens.show', $setup['kaizen']));

        $response->assertStatus(200);

        // Assert the visible trigger button has type="button" and is NOT type="submit"
        $response->assertSee('<button type="button" class="kf-btn kf-btn-primary px-4" data-bs-toggle="modal" data-bs-target="#approveModal">', false);
        $response->assertDontSee('<button type="submit" class="kf-btn kf-btn-primary px-4" data-bs-toggle="modal" data-bs-target="#approveModal">', false);
    }

    public function test_final_eligible_reviewer_page_visible_approve_trigger_is_not_post_submit_button()
    {
        $setup = $this->setupWorkflowAndUser(true);
        $response = $this->actingAs($setup['reviewer'])->get(route('kaizens.show', $setup['kaizen']));

        $response->assertStatus(200);

        $response->assertSee('<button type="button" class="kf-btn kf-btn-primary px-4" data-bs-toggle="modal" data-bs-target="#approveModal">', false);
        $response->assertDontSee('<button type="submit" class="kf-btn kf-btn-primary px-4" data-bs-toggle="modal" data-bs-target="#approveModal">', false);
    }

    public function test_final_stage_modal_is_rendered_with_correct_body()
    {
        $setup = $this->setupWorkflowAndUser(true);
        $response = $this->actingAs($setup['reviewer'])->get(route('kaizens.show', $setup['kaizen']));

        $response->assertStatus(200);

        // Modal is rendered
        $response->assertSee('<div class="modal fade" id="approveModal"', false);

        // Modal body has the final copy
        $response->assertSee('Bu son onay aşamasıdır. Onayladığınızda Kaizen onay süreci tamamlanacaktır.', false);
    }

    public function test_final_explicit_post_works_via_endpoint()
    {
        $setup = $this->setupWorkflowAndUser(true);

        // Ensure POST submit works
        $response = $this->actingAs($setup['reviewer'])
            ->post(route('kaizens.workflow.approve', $setup['kaizen']), [
                'comment' => 'LGTM',
            ]);

        $response->assertRedirect(route('approvals.index'));
        $this->assertEquals(KaizenStatus::APPROVED, $setup['kaizen']->fresh()->status);
    }

    public function test_unauthorized_user_does_not_see_panel_or_modal()
    {
        $setup = $this->setupWorkflowAndUser(true);
        // The creator of the Kaizen can view it but is not authorized to review it (since they are not in the approval group)
        $unauthorizedUser = $setup['kaizen']->creator;

        $response = $this->actingAs($unauthorizedUser)->get(route('kaizens.show', $setup['kaizen']));
        $response->assertStatus(200);

        $response->assertDontSee('data-bs-target="#approveModal"', false);
        $response->assertDontSee('id="approveModal"', false);
    }

    private function setupWorkflowAndUser($isFinal)
    {
        $reviewer = User::factory()->create();
        $workflow = ApprovalWorkflow::factory()->create();
        $stage = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id, 'is_final' => $isFinal]);

        $group = ApprovalGroup::factory()->create();
        ApprovalGroupMember::factory()->create(['approval_group_id' => $group->id, 'user_id' => $reviewer->id]);
        ApprovalStageAssignment::factory()->create(['approval_stage_id' => $stage->id, 'approval_group_id' => $group->id]);

        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::SUBMITTED]);
        KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $kaizen->id,
            'approval_workflow_id' => $workflow->id,
            'current_stage_id' => $stage->id,
        ]);

        return [
            'reviewer' => $reviewer,
            'kaizen' => $kaizen,
        ];
    }
}
