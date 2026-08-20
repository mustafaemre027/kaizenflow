<?php

namespace Tests\Feature\Workflow;

use App\Actions\Workflow\StartKaizenWorkflow;
use App\Enums\WorkflowAction;
use App\Models\ApprovalStage;
use App\Models\ApprovalWorkflow;
use App\Models\Kaizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StartKaizenWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private StartKaizenWorkflow $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = app(StartKaizenWorkflow::class);
    }

    public function test_it_starts_workflow_and_creates_instance_and_history()
    {
        $workflow = ApprovalWorkflow::factory()->create([
            'is_default' => true,
            'is_active' => true,
            'published_at' => now(),
        ]);

        $firstStage = ApprovalStage::factory()->create([
            'approval_workflow_id' => $workflow->id,
            'sequence' => 10,
            'is_final' => false,
        ]);

        ApprovalStage::factory()->create([
            'approval_workflow_id' => $workflow->id,
            'sequence' => 20,
            'is_final' => true,
        ]);

        $kaizen = Kaizen::factory()->create();
        $user = User::factory()->create();

        $instance = $this->action->execute($kaizen, $user);

        $this->assertDatabaseHas('kaizen_workflow_instances', [
            'kaizen_id' => $kaizen->id,
            'approval_workflow_id' => $workflow->id,
            'current_stage_id' => $firstStage->id,
        ]);

        $this->assertDatabaseHas('kaizen_workflow_transitions', [
            'kaizen_workflow_instance_id' => $instance->id,
            'kaizen_id' => $kaizen->id,
            'from_stage_id' => null,
            'to_stage_id' => $firstStage->id,
            'actor_user_id' => $user->id,
            'action' => WorkflowAction::START->value,
        ]);
    }

    public function test_it_prevents_duplicate_starts()
    {
        $workflow = ApprovalWorkflow::factory()->create([
            'is_default' => true,
            'is_active' => true,
            'published_at' => now(),
        ]);

        ApprovalStage::factory()->create([
            'approval_workflow_id' => $workflow->id,
            'is_final' => true,
        ]);

        $kaizen = Kaizen::factory()->create();
        $user = User::factory()->create();

        $this->action->execute($kaizen, $user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kaizen already has an active workflow instance.');

        $this->action->execute($kaizen, $user);
    }
}
