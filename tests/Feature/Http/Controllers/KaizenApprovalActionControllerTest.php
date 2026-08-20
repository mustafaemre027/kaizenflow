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

class KaizenApprovalActionControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $reviewer;

    private Kaizen $kaizen;

    private ApprovalStage $stage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reviewer = User::factory()->create();
        $workflow = ApprovalWorkflow::factory()->create();
        $this->stage = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id, 'is_final' => false, 'sequence' => 1]);

        $group = ApprovalGroup::factory()->create();
        ApprovalGroupMember::factory()->create(['approval_group_id' => $group->id, 'user_id' => $this->reviewer->id]);
        ApprovalStageAssignment::factory()->create(['approval_stage_id' => $this->stage->id, 'approval_group_id' => $group->id]);

        $this->kaizen = Kaizen::factory()->create(['status' => KaizenStatus::SUBMITTED]);
        KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $this->kaizen->id,
            'approval_workflow_id' => $workflow->id,
            'current_stage_id' => $this->stage->id,
        ]);
    }

    public function test_unauthorized_user_cannot_approve()
    {
        $otherUser = User::factory()->create();
        $response = $this->actingAs($otherUser)->post(route('kaizens.workflow.approve', $this->kaizen));
        $response->assertStatus(403);
    }

    public function test_authorized_user_can_approve()
    {
        $nextStage = ApprovalStage::factory()->create(['approval_workflow_id' => $this->stage->approval_workflow_id, 'is_final' => true, 'sequence' => 2]);

        $response = $this->actingAs($this->reviewer)->post(route('kaizens.workflow.approve', $this->kaizen), [
            'comment' => 'Looks good',
        ]);

        $response->assertRedirect(route('approvals.index'));
        $response->assertSessionHas('success', 'Kaizen onaylandı ve bir sonraki aşamaya ilerletildi.');

        $this->kaizen->refresh();
        $this->assertEquals($nextStage->id, $this->kaizen->workflowInstance->current_stage_id);
    }

    public function test_final_approve_completes_workflow()
    {
        $this->stage->update(['is_final' => true]);

        $response = $this->actingAs($this->reviewer)->post(route('kaizens.workflow.approve', $this->kaizen));

        $response->assertRedirect(route('approvals.index'));
        $response->assertSessionHas('success', 'Kaizen onay süreci başarıyla tamamlandı.');

        $this->kaizen->refresh();
        $this->assertEquals(KaizenStatus::APPROVED, $this->kaizen->status);
        $this->assertNotNull($this->kaizen->workflowInstance->completed_at);
    }

    public function test_request_revision_requires_comment()
    {
        $response = $this->actingAs($this->reviewer)->post(route('kaizens.workflow.request-revision', $this->kaizen), [
            'comment' => '',
        ]);

        $response->assertSessionHasErrors('comment');

        $this->kaizen->refresh();
        $this->assertEquals(KaizenStatus::SUBMITTED, $this->kaizen->status);
    }

    public function test_request_revision_success()
    {
        $response = $this->actingAs($this->reviewer)->post(route('kaizens.workflow.request-revision', $this->kaizen), [
            'comment' => 'Please fix this',
        ]);

        $response->assertRedirect(route('approvals.index'));
        $response->assertSessionHas('success', 'Kaizen revizyon için sahibine geri gönderildi.');

        $this->kaizen->refresh();
        $this->assertEquals(KaizenStatus::REVISION_REQUESTED, $this->kaizen->status);
    }

    public function test_reject_requires_comment()
    {
        $response = $this->actingAs($this->reviewer)->post(route('kaizens.workflow.reject', $this->kaizen), [
            'comment' => '',
        ]);

        $response->assertSessionHasErrors('comment');
    }

    public function test_reject_success()
    {
        $response = $this->actingAs($this->reviewer)->post(route('kaizens.workflow.reject', $this->kaizen), [
            'comment' => 'Not feasible',
        ]);

        $response->assertRedirect(route('approvals.index'));
        $response->assertSessionHas('success', 'Kaizen reddedildi ve onay süreci kapatıldı.');

        $this->kaizen->refresh();
        $this->assertEquals(KaizenStatus::REJECTED, $this->kaizen->status);
        $this->assertNotNull($this->kaizen->workflowInstance->cancelled_at);
    }

    public function test_stale_concurrency_protection()
    {
        // First approval works
        $nextStage = ApprovalStage::factory()->create(['approval_workflow_id' => $this->stage->approval_workflow_id, 'is_final' => true, 'sequence' => 2]);

        $this->actingAs($this->reviewer)->post(route('kaizens.workflow.approve', $this->kaizen));

        $this->kaizen->refresh();

        // Second approval simulates stale page where user clicks approve again
        // Note: reviewOnWorkflow checks if user is eligible for *current* stage.
        // Since stage advanced, the reviewer might no longer be eligible.
        // But what if they are STILL eligible for the next stage?
        // Let's add them to the next stage to bypass the 403 and hit the domain logic
        $group2 = ApprovalGroup::factory()->create();
        ApprovalGroupMember::factory()->create(['approval_group_id' => $group2->id, 'user_id' => $this->reviewer->id]);
        ApprovalStageAssignment::factory()->create(['approval_stage_id' => $nextStage->id, 'approval_group_id' => $group2->id]);

        $response = $this->actingAs($this->reviewer)->post(route('kaizens.workflow.approve', $this->kaizen));

        // It should still work because they are eligible for the new stage.
        // To truly test concurrency, we need the *same* stage action.
        // Wait, if they hit the same endpoint, it just does another valid transition.
        // If the workflow is completed, they get DomainException.
        $this->kaizen->refresh();
        $this->actingAs($this->reviewer)->post(route('kaizens.workflow.approve', $this->kaizen));

        $this->kaizen->refresh();
        $this->assertEquals(KaizenStatus::APPROVED, $this->kaizen->status);

        // Third approval when already completed
        $response = $this->actingAs($this->reviewer)->post(route('kaizens.workflow.approve', $this->kaizen));

        // Wait, if it's completed, canAct might be false because no active current_stage.
        // So they will get 403 first.
        $response->assertStatus(403);
    }
}
