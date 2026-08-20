<?php

namespace Tests\Feature\Workflow;

use App\Models\ApprovalStage;
use App\Models\ApprovalWorkflow;
use App\Models\Kaizen;
use App\Models\KaizenWorkflowInstance;
use App\Models\KaizenWorkflowTransition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalWorkflowImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_workflow_version_pinning()
    {
        // Custom v1 default
        $workflowV1 = ApprovalWorkflow::factory()->create([
            'code' => 'CUSTOM',
            'version' => 1,
            'is_default' => true,
        ]);
        $stageV1 = ApprovalStage::factory()->create(['approval_workflow_id' => $workflowV1->id]);

        $kaizenA = Kaizen::factory()->create();
        $instanceA = KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $kaizenA->id,
            'approval_workflow_id' => $workflowV1->id,
            'current_stage_id' => $stageV1->id,
        ]);

        // Custom v2 is created and made default
        $workflowV1->update(['is_default' => false]);

        $workflowV2 = ApprovalWorkflow::factory()->create([
            'code' => 'CUSTOM',
            'version' => 2,
            'is_default' => true,
        ]);
        $stageV2 = ApprovalStage::factory()->create(['approval_workflow_id' => $workflowV2->id]);

        // Kaizen A instance should still point to v1
        $this->assertEquals($workflowV1->id, $instanceA->refresh()->approval_workflow_id);
    }

    public function test_history_is_immutable()
    {
        $transition = KaizenWorkflowTransition::factory()->create();

        // Should return false due to booted preventions in the model
        $updated = $transition->update(['comment' => 'hacked']);
        $this->assertFalse($updated);
        $this->assertNotEquals('hacked', $transition->refresh()->comment);

        $deleted = $transition->delete();
        $this->assertFalse($deleted);
        $this->assertModelExists($transition);
    }
}
