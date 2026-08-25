<?php

namespace Tests\Feature\Workflow;

use App\Actions\ApprovalConfiguration\CreateApprovalWorkflowDraft;
use App\Actions\ApprovalConfiguration\PublishApprovalWorkflow;
use App\Actions\Workflow\ProgressKaizenWorkflow;
use App\Enums\ApprovalApproverScopeSource;
use App\Enums\ApproverResolutionMode;
use App\Enums\KaizenStatus;
use App\Enums\UserCapability;
use App\Enums\WorkflowAction;
use App\Exceptions\AuthorizationException;
use App\Exceptions\DomainException;
use App\Models\ApprovalGroup;
use App\Models\ApprovalStage;
use App\Models\ApprovalStageApproverRule;
use App\Models\ApprovalStageAssignment;
use App\Models\ApprovalWorkflow;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\KaizenWorkflowInstance;
use App\Models\User;
use App\Models\UserCapabilityGrant;
use App\Models\UserSystemCapabilityGrant;
use App\Services\AppendAuditLog;
use App\Services\Workflow\ApprovalStageApproverResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DynamicApproverRuntimeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;
    private Department $dept;
    private Kaizen $kaizen;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->actor = User::factory()->create(['is_active' => true]);
        $this->dept = Department::factory()->create();
        $this->kaizen = Kaizen::factory()->create([
            'department_id' => $this->dept->id,
            'creator_user_id' => User::factory()->create()->id,
            'status' => KaizenStatus::SUBMITTED,
        ]);
    }

    private function setupWorkflowAndInstance(ApproverResolutionMode $mode): array
    {
        $workflow = ApprovalWorkflow::factory()->create([
            'approver_resolution_mode' => $mode->value,
            'is_active' => true,
        ]);

        $stage = ApprovalStage::factory()->create([
            'approval_workflow_id' => $workflow->id,
            'is_active' => true,
            'is_final' => true,
        ]);

        $instance = KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $this->kaizen->id,
            'approval_workflow_id' => $workflow->id,
            'current_stage_id' => $stage->id,
            'started_at' => now(),
        ]);

        return [$workflow, $stage, $instance];
    }

    public function test_legacy_group_workflow_uses_legacy_resolver_only()
    {
        [$workflow, $stage, $instance] = $this->setupWorkflowAndInstance(ApproverResolutionMode::LEGACY_GROUP);

        $group = ApprovalGroup::factory()->create(['is_active' => true]);
        $group->members()->create(['user_id' => $this->actor->id, 'is_active' => true]);
        ApprovalStageAssignment::create([
            'approval_stage_id' => $stage->id,
            'approval_group_id' => $group->id,
            'scope' => 'GLOBAL',
            'is_active' => true,
        ]);

        // Attempting approval through Action
        $action = $this->app->make(ProgressKaizenWorkflow::class);
        $result = $action->execute($this->kaizen, $this->actor, WorkflowAction::APPROVE, 'Looks good');

        $this->assertEquals(KaizenStatus::APPROVED, $result->status);
    }

    public function test_capability_rule_workflow_uses_capability_resolver_only()
    {
        [$workflow, $stage, $instance] = $this->setupWorkflowAndInstance(ApproverResolutionMode::CAPABILITY_RULE);

        ApprovalStageApproverRule::factory()->create([
            'approval_stage_id' => $stage->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM,
            'is_active' => true,
        ]);

        UserSystemCapabilityGrant::create([
            'user_id' => $this->actor->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW,
            'is_active' => true,
        ]);

        $action = $this->app->make(ProgressKaizenWorkflow::class);
        $result = $action->execute($this->kaizen, $this->actor, WorkflowAction::APPROVE, 'Looks good');

        $this->assertEquals(KaizenStatus::APPROVED, $result->status);
    }

    public function test_capability_rule_failure_does_not_fallback_to_legacy()
    {
        [$workflow, $stage, $instance] = $this->setupWorkflowAndInstance(ApproverResolutionMode::CAPABILITY_RULE);

        // Has legacy assignment but missing capability rule
        $group = ApprovalGroup::factory()->create(['is_active' => true]);
        $group->members()->create(['user_id' => $this->actor->id, 'is_active' => true]);
        ApprovalStageAssignment::create([
            'approval_stage_id' => $stage->id,
            'approval_group_id' => $group->id,
            'scope' => 'GLOBAL',
            'is_active' => true,
        ]);

        $action = $this->app->make(ProgressKaizenWorkflow::class);
        $this->expectException(AuthorizationException::class);
        $action->execute($this->kaizen, $this->actor, WorkflowAction::APPROVE, 'Approve');
    }

    public function test_legacy_group_failure_does_not_fallback_to_capability()
    {
        [$workflow, $stage, $instance] = $this->setupWorkflowAndInstance(ApproverResolutionMode::LEGACY_GROUP);

        // Missing legacy assignment, but has capability match
        ApprovalStageApproverRule::factory()->create([
            'approval_stage_id' => $stage->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM,
            'is_active' => true,
        ]);
        UserSystemCapabilityGrant::create([
            'user_id' => $this->actor->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW,
            'is_active' => true,
        ]);

        $action = $this->app->make(ProgressKaizenWorkflow::class);
        $this->expectException(AuthorizationException::class);
        $action->execute($this->kaizen, $this->actor, WorkflowAction::APPROVE, 'Approve');
    }

    public function test_self_approval_is_prevented_and_rolls_back()
    {
        [$workflow, $stage, $instance] = $this->setupWorkflowAndInstance(ApproverResolutionMode::CAPABILITY_RULE);
        
        $this->kaizen->creator_user_id = $this->actor->id;
        $this->kaizen->save();

        ApprovalStageApproverRule::factory()->create([
            'approval_stage_id' => $stage->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM,
            'is_active' => true,
        ]);
        UserSystemCapabilityGrant::create([
            'user_id' => $this->actor->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW,
            'is_active' => true,
        ]);

        $action = $this->app->make(ProgressKaizenWorkflow::class);
        
        try {
            $action->execute($this->kaizen, $this->actor, WorkflowAction::APPROVE, 'Approve');
            $this->fail('Expected AuthorizationException');
        } catch (AuthorizationException $e) {
            $this->assertEquals(0, DB::table('kaizen_workflow_transitions')->count());
            $this->assertEquals(0, DB::table('kaizen_status_histories')->count());
        }
    }

    public function test_missing_or_inactive_rule_fails_closed()
    {
        [$workflow, $stage, $instance] = $this->setupWorkflowAndInstance(ApproverResolutionMode::CAPABILITY_RULE);

        ApprovalStageApproverRule::factory()->create([
            'approval_stage_id' => $stage->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM,
            'is_active' => false, // Inactive
        ]);

        $action = $this->app->make(ProgressKaizenWorkflow::class);
        $this->expectException(AuthorizationException::class);
        $action->execute($this->kaizen, $this->actor, WorkflowAction::APPROVE, 'Approve');
    }

    public function test_department_rule_works_with_matching_grant()
    {
        [$workflow, $stage, $instance] = $this->setupWorkflowAndInstance(ApproverResolutionMode::CAPABILITY_RULE);

        ApprovalStageApproverRule::factory()->create([
            'approval_stage_id' => $stage->id,
            'capability' => UserCapability::KAIZEN_DEPARTMENT_APPROVE,
            'scope_source' => ApprovalApproverScopeSource::KAIZEN_DEPARTMENT,
            'is_active' => true,
        ]);

        UserCapabilityGrant::create([
            'user_id' => $this->actor->id,
            'department_id' => $this->dept->id,
            'capability' => UserCapability::KAIZEN_DEPARTMENT_APPROVE,
            'is_active' => true,
        ]);

        $action = $this->app->make(ProgressKaizenWorkflow::class);
        $result = $action->execute($this->kaizen, $this->actor, WorkflowAction::APPROVE, 'Approve');

        $this->assertEquals(KaizenStatus::APPROVED, $result->status);
    }

    public function test_new_draft_is_explicitly_capability_rule_mode()
    {
        $admin = User::factory()->create();
        UserSystemCapabilityGrant::create([
            'user_id' => $admin->id,
            'capability' => UserCapability::APPROVAL_CONFIGURATION_MANAGE,
            'is_active' => true,
        ]);

        $action = $this->app->make(CreateApprovalWorkflowDraft::class);
        $workflow = $action->execute($admin, 'TEST', 'Test', null, [
            ['code' => 'STG1', 'name' => 'Stage 1', 'sequence' => 1, 'is_final' => true]
        ]);

        $this->assertEquals(ApproverResolutionMode::CAPABILITY_RULE, $workflow->approver_resolution_mode);
    }

    public function test_publish_capability_rule_workflow_requires_active_rule()
    {
        [$workflow, $stage, $instance] = $this->setupWorkflowAndInstance(ApproverResolutionMode::CAPABILITY_RULE);
        $workflow->update(['published_at' => null, 'is_active' => false]);
        $stage->update(['is_final' => true]); // make it final so publish can succeed otherwise

        $admin = User::factory()->create();
        UserSystemCapabilityGrant::create([
            'user_id' => $admin->id,
            'capability' => UserCapability::APPROVAL_CONFIGURATION_MANAGE,
            'is_active' => true,
        ]);

        $action = $this->app->make(PublishApprovalWorkflow::class);
        
        $this->expectException(DomainException::class);
        $action->execute($admin, $workflow);
    }

    public function test_publish_capability_rule_workflow_succeeds_with_rule()
    {
        [$workflow, $stage, $instance] = $this->setupWorkflowAndInstance(ApproverResolutionMode::CAPABILITY_RULE);
        $workflow->update(['published_at' => null, 'is_active' => false]);
        $stage->update(['is_final' => true]); 

        ApprovalStageApproverRule::factory()->create([
            'approval_stage_id' => $stage->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM,
            'is_active' => true,
        ]);

        $admin = User::factory()->create();
        UserSystemCapabilityGrant::create([
            'user_id' => $admin->id,
            'capability' => UserCapability::APPROVAL_CONFIGURATION_MANAGE,
            'is_active' => true,
        ]);

        $action = $this->app->make(PublishApprovalWorkflow::class);
        $published = $action->execute($admin, $workflow);
        $this->assertNotNull($published->published_at);
    }
}
