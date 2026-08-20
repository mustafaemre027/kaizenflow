<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\KaizenStatus;
use App\Enums\UserRole;
use App\Enums\WorkflowAction;
use App\Models\ApprovalGroup;
use App\Models\ApprovalGroupMember;
use App\Models\ApprovalStage;
use App\Models\ApprovalStageAssignment;
use App\Models\ApprovalWorkflow;
use App\Models\Kaizen;
use App\Models\KaizenWorkflowInstance;
use App\Models\KaizenWorkflowTransition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_redirects_to_login(): void
    {
        $response = $this->get(route('history.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_created_tab_shows_only_own_kaizens(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $own = Kaizen::factory()->create(['creator_user_id' => $user->id]);
        $theirs = Kaizen::factory()->create(['creator_user_id' => $other->id]);

        $response = $this->actingAs($user)->get(route('history.index', ['tab' => 'created']));

        $response->assertStatus(200);
        $kaizens = $response->viewData('createdKaizens');
        $this->assertTrue($kaizens->getCollection()->contains($own));
        $this->assertFalse($kaizens->getCollection()->contains($theirs));
    }

    public function test_created_tab_includes_terminal_statuses(): void
    {
        $user = User::factory()->create();
        $approved = Kaizen::factory()->create(['creator_user_id' => $user->id, 'status' => KaizenStatus::APPROVED]);
        $rejected = Kaizen::factory()->create(['creator_user_id' => $user->id, 'status' => KaizenStatus::REJECTED]);

        $response = $this->actingAs($user)->get(route('history.index', ['tab' => 'created']));

        $kaizens = $response->viewData('createdKaizens');
        $this->assertTrue($kaizens->getCollection()->contains($approved));
        $this->assertTrue($kaizens->getCollection()->contains($rejected));
    }

    public function test_created_tab_does_not_show_other_users_kaizens(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Kaizen::factory()->count(5)->create(['creator_user_id' => $other->id]);
        $own = Kaizen::factory()->create(['creator_user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('history.index', ['tab' => 'created']));

        $kaizens = $response->viewData('createdKaizens');
        $this->assertEquals(1, $kaizens->total());
        $this->assertTrue($kaizens->getCollection()->contains($own));
    }

    public function test_reviewed_tab_shows_own_approve_action(): void
    {
        $reviewer = User::factory()->create();
        $kaizen = Kaizen::factory()->create();
        $instance = KaizenWorkflowInstance::factory()->create(['kaizen_id' => $kaizen->id]);
        KaizenWorkflowTransition::factory()->create([
            'kaizen_id' => $kaizen->id,
            'kaizen_workflow_instance_id' => $instance->id,
            'actor_user_id' => $reviewer->id,
            'action' => WorkflowAction::APPROVE,
        ]);

        $response = $this->actingAs($reviewer)->get(route('history.index', ['tab' => 'reviewed']));

        $response->assertStatus(200);
        $transitions = $response->viewData('reviewedTransitions');
        $this->assertEquals(1, $transitions->total());
    }

    public function test_reviewed_tab_shows_own_request_revision_action(): void
    {
        $reviewer = User::factory()->create();
        $kaizen = Kaizen::factory()->create();
        $instance = KaizenWorkflowInstance::factory()->create(['kaizen_id' => $kaizen->id]);
        KaizenWorkflowTransition::factory()->create([
            'kaizen_id' => $kaizen->id,
            'kaizen_workflow_instance_id' => $instance->id,
            'actor_user_id' => $reviewer->id,
            'action' => WorkflowAction::REQUEST_REVISION,
        ]);

        $response = $this->actingAs($reviewer)->get(route('history.index', ['tab' => 'reviewed']));

        $transitions = $response->viewData('reviewedTransitions');
        $this->assertEquals(1, $transitions->total());
        $this->assertEquals(WorkflowAction::REQUEST_REVISION, $transitions->first()->action);
    }

    public function test_reviewed_tab_shows_own_reject_action(): void
    {
        $reviewer = User::factory()->create();
        $kaizen = Kaizen::factory()->create();
        $instance = KaizenWorkflowInstance::factory()->create(['kaizen_id' => $kaizen->id]);
        KaizenWorkflowTransition::factory()->create([
            'kaizen_id' => $kaizen->id,
            'kaizen_workflow_instance_id' => $instance->id,
            'actor_user_id' => $reviewer->id,
            'action' => WorkflowAction::REJECT,
        ]);

        $response = $this->actingAs($reviewer)->get(route('history.index', ['tab' => 'reviewed']));

        $transitions = $response->viewData('reviewedTransitions');
        $this->assertEquals(1, $transitions->total());
    }

    public function test_reviewed_tab_excludes_other_reviewers_actions(): void
    {
        $reviewer = User::factory()->create();
        // Give reviewer a valid action so they can access the tab
        KaizenWorkflowTransition::factory()->create(['actor_user_id' => $reviewer->id, 'action' => WorkflowAction::APPROVE]);

        $otherReviewer = User::factory()->create();
        $kaizen = Kaizen::factory()->create();
        $instance = KaizenWorkflowInstance::factory()->create(['kaizen_id' => $kaizen->id]);
        KaizenWorkflowTransition::factory()->create([
            'kaizen_id' => $kaizen->id,
            'kaizen_workflow_instance_id' => $instance->id,
            'actor_user_id' => $otherReviewer->id,
            'action' => WorkflowAction::APPROVE,
        ]);

        $response = $this->actingAs($reviewer)->get(route('history.index', ['tab' => 'reviewed']));

        $transitions = $response->viewData('reviewedTransitions');
        $this->assertEquals(1, $transitions->total()); // Only their own valid action, not the otherReviewer's
    }

    public function test_reviewed_tab_excludes_start_and_resubmit_actions(): void
    {
        $user = User::factory()->create();
        // Give user a valid action so they can access the tab
        KaizenWorkflowTransition::factory()->create(['actor_user_id' => $user->id, 'action' => WorkflowAction::APPROVE]);

        $kaizen = Kaizen::factory()->create(['creator_user_id' => $user->id]);
        $instance = KaizenWorkflowInstance::factory()->create(['kaizen_id' => $kaizen->id]);
        KaizenWorkflowTransition::factory()->create([
            'kaizen_id' => $kaizen->id, 'kaizen_workflow_instance_id' => $instance->id,
            'actor_user_id' => $user->id, 'action' => WorkflowAction::START,
        ]);
        KaizenWorkflowTransition::factory()->create([
            'kaizen_id' => $kaizen->id, 'kaizen_workflow_instance_id' => $instance->id,
            'actor_user_id' => $user->id, 'action' => WorkflowAction::RESUBMIT,
        ]);

        $response = $this->actingAs($user)->get(route('history.index', ['tab' => 'reviewed']));

        $transitions = $response->viewData('reviewedTransitions');
        $this->assertEquals(1, $transitions->total()); // Only the APPROVE, no START/RESUBMIT
    }

    public function test_multi_action_on_same_kaizen_shows_two_rows(): void
    {
        $reviewer = User::factory()->create();
        $kaizen = Kaizen::factory()->create();
        $instance = KaizenWorkflowInstance::factory()->create(['kaizen_id' => $kaizen->id]);
        KaizenWorkflowTransition::factory()->create([
            'kaizen_id' => $kaizen->id, 'kaizen_workflow_instance_id' => $instance->id,
            'actor_user_id' => $reviewer->id, 'action' => WorkflowAction::REQUEST_REVISION,
        ]);
        KaizenWorkflowTransition::factory()->create([
            'kaizen_id' => $kaizen->id, 'kaizen_workflow_instance_id' => $instance->id,
            'actor_user_id' => $reviewer->id, 'action' => WorkflowAction::APPROVE,
        ]);

        $response = $this->actingAs($reviewer)->get(route('history.index', ['tab' => 'reviewed']));

        $transitions = $response->viewData('reviewedTransitions');
        $this->assertEquals(2, $transitions->total());
        $actions = $transitions->getCollection()->pluck('action');
        $this->assertTrue($actions->contains(WorkflowAction::REQUEST_REVISION));
        $this->assertTrue($actions->contains(WorkflowAction::APPROVE));
    }

    public function test_created_status_filter_does_not_widen_scope(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Kaizen::factory()->create(['creator_user_id' => $other->id, 'status' => KaizenStatus::REJECTED]);

        $response = $this->actingAs($user)->get(route('history.index', [
            'tab' => 'created', 'status' => KaizenStatus::REJECTED->value,
        ]));

        $kaizens = $response->viewData('createdKaizens');
        $this->assertEquals(0, $kaizens->total());
    }

    public function test_reviewed_action_filter_does_not_widen_scope(): void
    {
        $reviewer = User::factory()->create();
        // Give reviewer a valid action so they can access the tab
        KaizenWorkflowTransition::factory()->create(['actor_user_id' => $reviewer->id, 'action' => WorkflowAction::APPROVE]);

        $other = User::factory()->create();
        $kaizen = Kaizen::factory()->create();
        $instance = KaizenWorkflowInstance::factory()->create(['kaizen_id' => $kaizen->id]);
        KaizenWorkflowTransition::factory()->create([
            'kaizen_id' => $kaizen->id, 'kaizen_workflow_instance_id' => $instance->id,
            'actor_user_id' => $other->id, 'action' => WorkflowAction::APPROVE,
        ]);

        $response = $this->actingAs($reviewer)->get(route('history.index', [
            'tab' => 'reviewed', 'action' => WorkflowAction::APPROVE->value,
        ]));

        $transitions = $response->viewData('reviewedTransitions');
        $this->assertEquals(1, $transitions->total()); // Only their own
    }

    public function test_reviewed_filter_rejects_non_review_action_values(): void
    {
        $user = User::factory()->create();
        // Give user a valid action so they can access the tab
        KaizenWorkflowTransition::factory()->create(['actor_user_id' => $user->id, 'action' => WorkflowAction::APPROVE]);

        $kaizen = Kaizen::factory()->create(['creator_user_id' => $user->id]);
        $instance = KaizenWorkflowInstance::factory()->create(['kaizen_id' => $kaizen->id]);
        KaizenWorkflowTransition::factory()->create([
            'kaizen_id' => $kaizen->id, 'kaizen_workflow_instance_id' => $instance->id,
            'actor_user_id' => $user->id, 'action' => WorkflowAction::START,
        ]);

        $response = $this->actingAs($user)->get(route('history.index', [
            'tab' => 'reviewed', 'action' => WorkflowAction::START->value,
        ]));

        $transitions = $response->viewData('reviewedTransitions');
        $this->assertEquals(1, $transitions->total()); // The filter is ignored, so it falls back to all reviews (which is just 1 APPROVE)
    }

    public function test_pagination_works_for_created_tab(): void
    {
        $user = User::factory()->create();
        Kaizen::factory()->count(20)->create(['creator_user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('history.index', ['tab' => 'created']));

        $kaizens = $response->viewData('createdKaizens');
        $this->assertEquals(15, $kaizens->count());
        $this->assertTrue($kaizens->hasPages());
        $this->assertEquals(20, $kaizens->total());
    }

    public function test_pagination_works_for_reviewed_tab(): void
    {
        $reviewer = User::factory()->create();
        $kaizen = Kaizen::factory()->create();
        $instance = KaizenWorkflowInstance::factory()->create(['kaizen_id' => $kaizen->id]);
        KaizenWorkflowTransition::factory()->count(20)->create([
            'kaizen_id' => $kaizen->id,
            'kaizen_workflow_instance_id' => $instance->id,
            'actor_user_id' => $reviewer->id,
            'action' => WorkflowAction::APPROVE,
        ]);

        $response = $this->actingAs($reviewer)->get(route('history.index', ['tab' => 'reviewed']));

        $transitions = $response->viewData('reviewedTransitions');
        $this->assertEquals(15, $transitions->count());
        $this->assertTrue($transitions->hasPages());
        $this->assertEquals(20, $transitions->total());
    }

    public function test_idor_attempt_returns_only_own_scope(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();
        Kaizen::factory()->count(3)->create(['creator_user_id' => $target->id]);
        $own = Kaizen::factory()->create(['creator_user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('history.index', [
            'tab' => 'created', 'creator_user_id' => $target->id, 'user_id' => $target->id,
        ]));

        $kaizens = $response->viewData('createdKaizens');
        $this->assertEquals(1, $kaizens->total());
        $this->assertTrue($kaizens->getCollection()->contains($own));
    }

    public function test_past_actor_can_view_kaizen_they_acted_on(): void
    {
        $reviewer = User::factory()->create();
        $creator = User::factory()->create();
        $kaizen = Kaizen::factory()->create(['creator_user_id' => $creator->id, 'status' => KaizenStatus::APPROVED]);
        $instance = KaizenWorkflowInstance::factory()->create(['kaizen_id' => $kaizen->id]);
        KaizenWorkflowTransition::factory()->create([
            'kaizen_id' => $kaizen->id, 'kaizen_workflow_instance_id' => $instance->id,
            'actor_user_id' => $reviewer->id, 'action' => WorkflowAction::APPROVE,
        ]);

        $this->assertTrue($reviewer->can('view', $kaizen));

        $response = $this->actingAs($reviewer)->get(route('history.index', ['tab' => 'reviewed']));
        $response->assertStatus(200);
        $response->assertSee(route('kaizens.show', $kaizen));
    }

    public function test_non_actor_non_creator_cannot_view_kaizen(): void
    {
        $stranger = User::factory()->create();
        $creator = User::factory()->create();
        $kaizen = Kaizen::factory()->create(['creator_user_id' => $creator->id, 'status' => KaizenStatus::APPROVED]);

        $this->assertFalse($stranger->can('view', $kaizen));
    }

    public function test_same_historical_actor_cannot_update(): void
    {
        $reviewer = User::factory()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::APPROVED]);
        KaizenWorkflowTransition::factory()->create([
            'kaizen_id' => $kaizen->id,
            'actor_user_id' => $reviewer->id,
            'action' => WorkflowAction::APPROVE,
        ]);

        $this->assertTrue($reviewer->can('view', $kaizen));
        $this->assertFalse($reviewer->can('update', $kaizen));

        $response = $this->actingAs($reviewer)->patch(route('kaizens.update', $kaizen), ['title' => 'Hacked']);
        $response->assertStatus(403);
    }

    public function test_same_historical_actor_cannot_submit(): void
    {
        $reviewer = User::factory()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::DRAFT]);
        KaizenWorkflowTransition::factory()->create([
            'kaizen_id' => $kaizen->id,
            'actor_user_id' => $reviewer->id,
            'action' => WorkflowAction::APPROVE, // Mock past action
        ]);

        $this->assertFalse($reviewer->can('submit', $kaizen));

        $response = $this->actingAs($reviewer)->post(route('kaizens.submit', $kaizen));
        $response->assertStatus(403);
    }

    public function test_same_historical_actor_cannot_perform_review_actions_on_stale_record(): void
    {
        $reviewer = User::factory()->create();
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::APPROVED]);
        KaizenWorkflowTransition::factory()->create([
            'kaizen_id' => $kaizen->id,
            'actor_user_id' => $reviewer->id,
            'action' => WorkflowAction::APPROVE,
        ]);

        $this->assertFalse($reviewer->can('reviewOnWorkflow', $kaizen));

        // D. approve
        $response = $this->actingAs($reviewer)->post(route('kaizens.workflow.approve', $kaizen));
        $response->assertStatus(403);

        // E. request revision
        $response = $this->actingAs($reviewer)->post(route('kaizens.workflow.request-revision', $kaizen), ['comment' => 'Rev']);
        $response->assertStatus(403);

        // F. reject
        $response = $this->actingAs($reviewer)->post(route('kaizens.workflow.reject', $kaizen), ['comment' => 'Rej']);
        $response->assertStatus(403);
    }

    public function test_group_member_who_never_acted_does_not_gain_historical_view_merely_because_of_membership(): void
    {
        $groupMember = User::factory()->create();
        $workflow = ApprovalWorkflow::factory()->create();
        $stage = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id]);
        $group = ApprovalGroup::factory()->create();
        ApprovalGroupMember::factory()->create(['approval_group_id' => $group->id, 'user_id' => $groupMember->id]);
        ApprovalStageAssignment::factory()->create(['approval_stage_id' => $stage->id, 'approval_group_id' => $group->id]);

        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::APPROVED]);
        // Note: No KaizenWorkflowTransition exists for this user on this kaizen.
        // It's not in SUBMITTED state, and they are not the assigned user.

        $this->assertFalse($groupMember->can('view', $kaizen));
    }

    public function test_approvals_inbox_still_shows_only_actionable_submitted_records(): void
    {
        $reviewer = User::factory()->create();
        $workflow = ApprovalWorkflow::factory()->create();
        $stage = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id]);
        $group = ApprovalGroup::factory()->create();
        ApprovalGroupMember::factory()->create(['approval_group_id' => $group->id, 'user_id' => $reviewer->id]);
        ApprovalStageAssignment::factory()->create(['approval_stage_id' => $stage->id, 'approval_group_id' => $group->id]);

        $submitted = Kaizen::factory()->create(['status' => KaizenStatus::SUBMITTED]);
        KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $submitted->id, 'approval_workflow_id' => $workflow->id, 'current_stage_id' => $stage->id,
        ]);
        $approved = Kaizen::factory()->create(['status' => KaizenStatus::APPROVED]);
        $revision = Kaizen::factory()->create(['status' => KaizenStatus::REVISION_REQUESTED]);

        $response = $this->actingAs($reviewer)->get(route('approvals.index'));

        $approvals = $response->viewData('approvals');
        $this->assertTrue($approvals->getCollection()->contains($submitted));
        $this->assertFalse($approvals->getCollection()->contains($approved));
        $this->assertFalse($approvals->getCollection()->contains($revision));
    }

    public function test_plain_employee_does_not_see_reviewed_tab(): void
    {
        $employee = User::factory()->create(['role' => UserRole::EMPLOYEE]);

        $response = $this->actingAs($employee)->get(route('history.index'));
        $response->assertStatus(200);

        $this->assertFalse($response->viewData('canAccessReviewedHistory'));
        $response->assertDontSee('tab-reviewed');
    }

    public function test_plain_employee_requesting_reviewed_tab_is_normalized_to_created(): void
    {
        $employee = User::factory()->create();

        $response = $this->actingAs($employee)->get(route('history.index', ['tab' => 'reviewed']));
        $response->assertStatus(200);

        $this->assertFalse($response->viewData('canAccessReviewedHistory'));
        $this->assertEquals('created', $response->viewData('activeTab'));
        $this->assertNotNull($response->viewData('createdKaizens'));
        $this->assertNull($response->viewData('reviewedTransitions'));
    }

    public function test_active_approval_group_member_sees_reviewed_tab(): void
    {
        $member = User::factory()->create();
        $group = ApprovalGroup::factory()->create(['is_active' => true]);
        ApprovalGroupMember::factory()->create([
            'approval_group_id' => $group->id,
            'user_id' => $member->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($member)->get(route('history.index'));
        $response->assertStatus(200);

        $this->assertTrue($response->viewData('canAccessReviewedHistory'));
        $response->assertSee('tab-reviewed');
    }

    public function test_historical_actor_no_longer_active_member_sees_reviewed_tab(): void
    {
        $actor = User::factory()->create();
        // Not a member of any group, but has a past transition
        KaizenWorkflowTransition::factory()->create([
            'actor_user_id' => $actor->id,
            'action' => WorkflowAction::APPROVE,
        ]);

        $response = $this->actingAs($actor)->get(route('history.index'));
        $response->assertStatus(200);

        $this->assertTrue($response->viewData('canAccessReviewedHistory'));
        $response->assertSee('tab-reviewed');
    }

    public function test_role_alone_does_not_determine_visibility(): void
    {
        // Manager with no active groups and no past transitions
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);

        $response = $this->actingAs($manager)->get(route('history.index'));
        $response->assertStatus(200);

        $this->assertFalse($response->viewData('canAccessReviewedHistory'));
        $response->assertDontSee('tab-reviewed');
    }
}
