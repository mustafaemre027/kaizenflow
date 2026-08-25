<?php

namespace Tests\Feature\Workflow;

use App\Enums\ApprovalApproverScopeSource;
use App\Enums\UserCapability;
use App\Models\ApprovalStage;
use App\Models\ApprovalStageApproverRule;
use App\Models\ApprovalWorkflow;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\KaizenWorkflowInstance;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use App\Models\UserCapabilityGrant;
use App\Services\Workflow\CapabilityApprovalStageApproverResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CapabilityApprovalStageApproverResolverTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;
    private Department $department1;
    private Department $department2;
    private Kaizen $kaizen;
    private ApprovalWorkflow $workflow;
    private ApprovalStage $stage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::factory()->create(['is_active' => true]);
        
        $this->department1 = Department::factory()->create();
        $this->department2 = Department::factory()->create();

        $this->workflow = ApprovalWorkflow::factory()->create(['approver_resolution_mode' => 'CAPABILITY_RULE', 'is_active' => true]);
        $this->stage = ApprovalStage::factory()->create(['approval_workflow_id' => $this->workflow->id, 'is_active' => true]);
        
        $this->kaizen = Kaizen::factory()->create([
            'department_id' => $this->department1->id,
            'creator_user_id' => User::factory()->create()->id,
        ]);

        KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $this->kaizen->id,
            'approval_workflow_id' => $this->workflow->id,
            'current_approval_stage_id' => $this->stage->id,
            'completed_at' => null,
            'cancelled_at' => null,
        ]);
    }

    public function test_it_returns_false_if_actor_is_inactive()
    {
        $this->actor->update(['is_active' => false]);
        $resolver = $this->app->make(CapabilityApprovalStageApproverResolver::class);
        $this->assertFalse($resolver->canAct($this->actor, $this->kaizen, $this->stage));
    }

    public function test_it_returns_false_if_stage_is_inactive()
    {
        $this->stage->update(['is_active' => false]);
        $resolver = $this->app->make(CapabilityApprovalStageApproverResolver::class);
        $this->assertFalse($resolver->canAct($this->actor, $this->kaizen, $this->stage));
    }

    public function test_it_returns_false_if_workflow_is_legacy_group()
    {
        $this->workflow->update(['approver_resolution_mode' => 'LEGACY_GROUP']);
        $resolver = $this->app->make(CapabilityApprovalStageApproverResolver::class);
        $this->assertFalse($resolver->canAct($this->actor, $this->kaizen, $this->stage));
    }

    public function test_it_returns_false_if_rule_is_missing()
    {
        $resolver = $this->app->make(CapabilityApprovalStageApproverResolver::class);
        $this->assertFalse($resolver->canAct($this->actor, $this->kaizen, $this->stage));
    }

    public function test_it_returns_false_if_rule_is_inactive()
    {
        ApprovalStageApproverRule::factory()->create([
            'approval_stage_id' => $this->stage->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM,
            'is_active' => false,
        ]);
        
        $resolver = $this->app->make(CapabilityApprovalStageApproverResolver::class);
        $this->assertFalse($resolver->canAct($this->actor, $this->kaizen, $this->stage));
    }

    public function test_self_approval_is_prevented()
    {
        $this->kaizen->update(['creator_user_id' => $this->actor->id]);
        
        ApprovalStageApproverRule::factory()->create([
            'approval_stage_id' => $this->stage->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM,
            'is_active' => true,
        ]);

        UserSystemCapabilityGrant::create([
            'user_id' => $this->actor->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW,
            'is_active' => true,
        ]);

        $resolver = $this->app->make(CapabilityApprovalStageApproverResolver::class);
        $this->assertFalse($resolver->canAct($this->actor, $this->kaizen, $this->stage));
    }

    public function test_system_rule_with_active_system_grant_succeeds()
    {
        ApprovalStageApproverRule::factory()->create([
            'approval_stage_id' => $this->stage->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM,
            'is_active' => true,
        ]);

        UserSystemCapabilityGrant::create([
            'user_id' => $this->actor->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW,
            'is_active' => true,
        ]);

        $resolver = $this->app->make(CapabilityApprovalStageApproverResolver::class);
        $this->assertTrue($resolver->canAct($this->actor, $this->kaizen, $this->stage));
    }

    public function test_system_rule_with_inactive_system_grant_fails()
    {
        ApprovalStageApproverRule::factory()->create([
            'approval_stage_id' => $this->stage->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM,
            'is_active' => true,
        ]);

        UserSystemCapabilityGrant::create([
            'user_id' => $this->actor->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW,
            'is_active' => false,
        ]);

        $resolver = $this->app->make(CapabilityApprovalStageApproverResolver::class);
        $this->assertFalse($resolver->canAct($this->actor, $this->kaizen, $this->stage));
    }

    public function test_department_rule_with_matching_department_grant_succeeds()
    {
        ApprovalStageApproverRule::factory()->create([
            'approval_stage_id' => $this->stage->id,
            'capability' => UserCapability::KAIZEN_DEPARTMENT_APPROVE,
            'scope_source' => ApprovalApproverScopeSource::KAIZEN_DEPARTMENT,
            'is_active' => true,
        ]);

        UserCapabilityGrant::create([
            'user_id' => $this->actor->id,
            'department_id' => $this->department1->id,
            'capability' => UserCapability::KAIZEN_DEPARTMENT_APPROVE,
            'is_active' => true,
        ]);

        $resolver = $this->app->make(CapabilityApprovalStageApproverResolver::class);
        $this->assertTrue($resolver->canAct($this->actor, $this->kaizen, $this->stage));
    }

    public function test_department_rule_with_wrong_department_grant_fails()
    {
        ApprovalStageApproverRule::factory()->create([
            'approval_stage_id' => $this->stage->id,
            'capability' => UserCapability::KAIZEN_DEPARTMENT_APPROVE,
            'scope_source' => ApprovalApproverScopeSource::KAIZEN_DEPARTMENT,
            'is_active' => true,
        ]);

        // Actor has grant for dept 2, but kaizen is in dept 1
        UserCapabilityGrant::create([
            'user_id' => $this->actor->id,
            'department_id' => $this->department2->id,
            'capability' => UserCapability::KAIZEN_DEPARTMENT_APPROVE,
            'is_active' => true,
        ]);

        $resolver = $this->app->make(CapabilityApprovalStageApproverResolver::class);
        $this->assertFalse($resolver->canAct($this->actor, $this->kaizen, $this->stage));
    }

    public function test_role_does_not_bypass()
    {
        ApprovalStageApproverRule::factory()->create([
            'approval_stage_id' => $this->stage->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM,
            'is_active' => true,
        ]);
        
        $this->actor->update(['role' => \App\Enums\UserRole::ADMIN]);

        $resolver = $this->app->make(CapabilityApprovalStageApproverResolver::class);
        $this->assertFalse($resolver->canAct($this->actor, $this->kaizen, $this->stage));
    }
}
