<?php

namespace Tests\Feature;

use App\Enums\ApprovalApproverScopeSource;
use App\Enums\ApproverResolutionMode;
use App\Enums\UserCapability;
use App\Models\ApprovalStage;
use App\Models\ApprovalStageApproverRule;
use App\Models\ApprovalWorkflow;
use App\Models\Kaizen;
use App\Models\KaizenWorkflowInstance;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use App\Services\Workflow\ApprovalStageNotificationRecipientResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalStageNotificationRecipientResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_capability_recipient_resolution()
    {
        $resolver = new ApprovalStageNotificationRecipientResolver;

        $creator = User::factory()->create();
        $kaizen = Kaizen::factory()->create(['creator_user_id' => $creator->id]);

        $workflow = ApprovalWorkflow::factory()->create([
            'approver_resolution_mode' => ApproverResolutionMode::CAPABILITY_RULE,
        ]);

        $stage = ApprovalStage::factory()->create([
            'approval_workflow_id' => $workflow->id,
            'is_active' => true,
        ]);

        ApprovalStageApproverRule::factory()->create([
            'approval_stage_id' => $stage->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM,
            'is_active' => true,
        ]);

        $instance = KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $kaizen->id,
            'approval_workflow_id' => $workflow->id,
            'current_stage_id' => $stage->id,
        ]);

        $eligible = User::factory()->create(['is_active' => true, 'must_set_password' => false]);
        UserSystemCapabilityGrant::create([
            'user_id' => $eligible->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW,
            'is_active' => true,
        ]);

        $inactive = User::factory()->create(['is_active' => false, 'must_set_password' => false]);
        UserSystemCapabilityGrant::create([
            'user_id' => $inactive->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW,
            'is_active' => true,
        ]);

        $recipients = $resolver->resolveCurrentStage($kaizen);

        $this->assertCount(1, $recipients);
        $this->assertEquals($eligible->id, $recipients->first()->id);
    }
}
