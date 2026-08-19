<?php

namespace Tests\Feature\Workflow;

use App\Actions\Workflow\ProgressKaizenWorkflow;
use App\Enums\KaizenStatus;
use App\Enums\WorkflowAction;
use App\Exceptions\Workflow\InvalidApprovalWorkflowConfiguration;
use App\Models\ApprovalStage;
use App\Models\ApprovalWorkflow;
use App\Models\Kaizen;
use App\Models\KaizenWorkflowInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProgressKaizenWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private ProgressKaizenWorkflow $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = app(ProgressKaizenWorkflow::class);
    }

    private function setupWorkflowAndInstance(int $stageCount, array $sequences = []): array
    {
        $workflow = ApprovalWorkflow::factory()->create(['is_active' => true, 'is_default' => true]);
        $stages = [];

        for ($i = 0; $i < $stageCount; $i++) {
            $stages[] = ApprovalStage::factory()->create([
                'approval_workflow_id' => $workflow->id,
                'sequence' => $sequences[$i] ?? ($i + 1) * 10,
                'is_final' => $i === ($stageCount - 1),
            ]);
        }

        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::SUBMITTED]);

        $instance = KaizenWorkflowInstance::factory()->create([
            'kaizen_id' => $kaizen->id,
            'approval_workflow_id' => $workflow->id,
            'current_stage_id' => $stages[0]->id,
        ]);

        return [$workflow, $stages, $kaizen, $instance];
    }

    public function test_arbitrary_three_stages_progression()
    {
        [$workflow, $stages, $kaizen, $instance] = $this->setupWorkflowAndInstance(3);
        $actor = User::factory()->create();

        // 1st approve
        $kaizen = $this->action->execute($kaizen, $actor, WorkflowAction::APPROVE);
        $this->assertEquals($stages[1]->id, $instance->refresh()->current_stage_id);
        $this->assertEquals(KaizenStatus::SUBMITTED, $kaizen->status);
        $this->assertDatabaseHas('kaizen_workflow_transitions', [
            'kaizen_id' => $kaizen->id,
            'from_stage_id' => $stages[0]->id,
            'to_stage_id' => $stages[1]->id,
            'action' => WorkflowAction::APPROVE->value,
        ]);
        $this->assertDatabaseMissing('kaizen_status_histories', ['transition_code' => 'APPROVE']);

        // 2nd approve
        $kaizen = $this->action->execute($kaizen, $actor, WorkflowAction::APPROVE);
        $this->assertEquals($stages[2]->id, $instance->refresh()->current_stage_id);
        $this->assertEquals(KaizenStatus::SUBMITTED, $kaizen->status);

        // 3rd approve (final)
        $kaizen = $this->action->execute($kaizen, $actor, WorkflowAction::APPROVE);
        $this->assertEquals($stages[2]->id, $instance->refresh()->current_stage_id); // preserves final stage
        $this->assertEquals(KaizenStatus::APPROVED, $kaizen->status);
        $this->assertNotNull($instance->refresh()->completed_at);
        $this->assertDatabaseHas('kaizen_status_histories', [
            'transition_code' => 'APPROVE',
            'from_status' => KaizenStatus::SUBMITTED->value,
            'to_status' => KaizenStatus::APPROVED->value,
        ]);
    }

    public function test_five_stages_non_contiguous_sequence()
    {
        [$workflow, $stages, $kaizen, $instance] = $this->setupWorkflowAndInstance(5, [10, 40, 70, 95, 120]);
        $actor = User::factory()->create();

        for ($i = 0; $i < 4; $i++) {
            $kaizen = $this->action->execute($kaizen, $actor, WorkflowAction::APPROVE);
            $this->assertEquals($stages[$i + 1]->id, $instance->refresh()->current_stage_id);
            $this->assertEquals(KaizenStatus::SUBMITTED, $kaizen->status);
        }

        // Final
        $kaizen = $this->action->execute($kaizen, $actor, WorkflowAction::APPROVE);
        $this->assertEquals(KaizenStatus::APPROVED, $kaizen->status);
        $this->assertNotNull($instance->refresh()->completed_at);
    }

    public function test_request_revision()
    {
        [$workflow, $stages, $kaizen, $instance] = $this->setupWorkflowAndInstance(3);
        $actor = User::factory()->create();

        $kaizen = $this->action->execute($kaizen, $actor, WorkflowAction::REQUEST_REVISION, 'Need more info');

        $this->assertEquals(KaizenStatus::REVISION_REQUESTED, $kaizen->status);
        $this->assertEquals($stages[0]->id, $instance->refresh()->current_stage_id);
        $this->assertNull($instance->completed_at);
        $this->assertNull($instance->cancelled_at);

        $this->assertDatabaseHas('kaizen_workflow_transitions', [
            'action' => WorkflowAction::REQUEST_REVISION->value,
            'from_stage_id' => $stages[0]->id,
            'to_stage_id' => $stages[0]->id,
            'comment' => 'Need more info',
        ]);

        $this->assertDatabaseHas('kaizen_status_histories', [
            'transition_code' => 'REQUEST_REVISION',
            'to_status' => KaizenStatus::REVISION_REQUESTED->value,
        ]);
    }

    public function test_request_revision_comment_required()
    {
        [$workflow, $stages, $kaizen, $instance] = $this->setupWorkflowAndInstance(3);
        $actor = User::factory()->create();

        $this->expectException(ValidationException::class);
        $this->action->execute($kaizen, $actor, WorkflowAction::REQUEST_REVISION, '   ');
    }

    public function test_reject()
    {
        [$workflow, $stages, $kaizen, $instance] = $this->setupWorkflowAndInstance(3);
        $actor = User::factory()->create();

        $kaizen = $this->action->execute($kaizen, $actor, WorkflowAction::REJECT, 'Not feasible');

        $this->assertEquals(KaizenStatus::REJECTED, $kaizen->status);
        $this->assertEquals($stages[0]->id, $instance->refresh()->current_stage_id);
        $this->assertNull($instance->completed_at);
        $this->assertNotNull($instance->cancelled_at);

        $this->assertDatabaseHas('kaizen_workflow_transitions', [
            'action' => WorkflowAction::REJECT->value,
            'from_stage_id' => $stages[0]->id,
            'to_stage_id' => $stages[0]->id,
            'comment' => 'Not feasible',
        ]);

        $this->assertDatabaseHas('kaizen_status_histories', [
            'transition_code' => 'REJECT',
            'to_status' => KaizenStatus::REJECTED->value,
        ]);
    }

    public function test_reject_comment_required()
    {
        [$workflow, $stages, $kaizen, $instance] = $this->setupWorkflowAndInstance(3);
        $actor = User::factory()->create();

        $this->expectException(ValidationException::class);
        $this->action->execute($kaizen, $actor, WorkflowAction::REJECT, '');
    }

    public function test_final_config_error()
    {
        [$workflow, $stages, $kaizen, $instance] = $this->setupWorkflowAndInstance(1);
        $stages[0]->update(['is_final' => false]); // Invalid config

        $actor = User::factory()->create();

        $this->expectException(InvalidApprovalWorkflowConfiguration::class);
        $this->action->execute($kaizen, $actor, WorkflowAction::APPROVE);
    }

    public function test_completed_guard()
    {
        [$workflow, $stages, $kaizen, $instance] = $this->setupWorkflowAndInstance(3);
        $instance->update(['completed_at' => now()]);
        $actor = User::factory()->create();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Workflow instance is already closed.');
        $this->action->execute($kaizen, $actor, WorkflowAction::APPROVE);
    }

    public function test_cancelled_guard()
    {
        [$workflow, $stages, $kaizen, $instance] = $this->setupWorkflowAndInstance(3);
        $instance->update(['cancelled_at' => now()]);
        $actor = User::factory()->create();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Workflow instance is already closed.');
        $this->action->execute($kaizen, $actor, WorkflowAction::APPROVE);
    }

    public function test_wrong_lifecycle()
    {
        [$workflow, $stages, $kaizen, $instance] = $this->setupWorkflowAndInstance(3);
        $kaizen->status = KaizenStatus::DRAFT;
        $kaizen->save();
        $actor = User::factory()->create();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Kaizen must be in SUBMITTED state to progress workflow.');
        $this->action->execute($kaizen, $actor, WorkflowAction::APPROVE);
    }

    public function test_cross_workflow_current_stage()
    {
        [$workflowA, $stagesA, $kaizen, $instance] = $this->setupWorkflowAndInstance(3);
        $workflowB = ApprovalWorkflow::factory()->create();
        $stageB = ApprovalStage::factory()->create(['approval_workflow_id' => $workflowB->id]);

        $instance->update(['current_stage_id' => $stageB->id]);
        $actor = User::factory()->create();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Workflow current stage is corrupted.');
        $this->action->execute($kaizen, $actor, WorkflowAction::APPROVE);
    }
}
