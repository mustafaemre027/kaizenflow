<?php

namespace Tests\Feature\Services;

use App\Enums\KaizenStatus;
use App\Enums\WorkflowAction;
use App\Models\ApprovalStage;
use App\Models\ApprovalWorkflow;
use App\Models\Kaizen;
use App\Models\KaizenWorkflowInstance;
use App\Models\User;
use App\Services\Workflow\KaizenWorkflowTimelinePresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KaizenWorkflowTimelinePresenterTest extends TestCase
{
    use RefreshDatabase;

    private KaizenWorkflowTimelinePresenter $presenter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->presenter = app(KaizenWorkflowTimelinePresenter::class);
    }

    private function setupWorkflowAndInstance(int $stageCount, array $sequences = []): array
    {
        $workflow = ApprovalWorkflow::factory()->create(['name' => 'Test Workflow', 'is_active' => true, 'is_default' => true]);
        $stages = [];

        for ($i = 0; $i < $stageCount; $i++) {
            $stages[] = ApprovalStage::factory()->create([
                'approval_workflow_id' => $workflow->id,
                'name' => 'Stage '.($i + 1),
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

    public function test_draft_state()
    {
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::DRAFT]);

        $result = $this->presenter->present($kaizen);

        $this->assertFalse($result->isAvailable);
        $this->assertTrue($result->isDraft);
        $this->assertNull($result->workflowName);
        $this->assertEmpty($result->stages);
        $this->assertEmpty($result->history);
    }

    public function test_legacy_state()
    {
        $kaizen = Kaizen::factory()->create(['status' => KaizenStatus::SUBMITTED]);

        $result = $this->presenter->present($kaizen);

        $this->assertFalse($result->isAvailable);
        $this->assertFalse($result->isDraft);
        $this->assertNull($result->workflowName);
    }

    public function test_three_stage_current_second()
    {
        [$workflow, $stages, $kaizen, $instance] = $this->setupWorkflowAndInstance(3);
        $instance->update(['current_stage_id' => $stages[1]->id]);

        $result = $this->presenter->present($kaizen);

        $this->assertTrue($result->isAvailable);
        $this->assertEquals('Test Workflow', $result->workflowName);
        $this->assertCount(3, $result->stages);

        $this->assertEquals('completed', $result->stages[0]->presentation_state);
        $this->assertEquals('current', $result->stages[1]->presentation_state);
        $this->assertEquals('upcoming', $result->stages[2]->presentation_state);
    }

    public function test_five_stages_non_contiguous()
    {
        [$workflow, $stages, $kaizen, $instance] = $this->setupWorkflowAndInstance(5, [10, 40, 100, 200, 300]);
        $instance->update(['current_stage_id' => $stages[2]->id]);

        $result = $this->presenter->present($kaizen);

        $this->assertCount(5, $result->stages);
        $this->assertEquals('completed', $result->stages[0]->presentation_state);
        $this->assertEquals('completed', $result->stages[1]->presentation_state);
        $this->assertEquals('current', $result->stages[2]->presentation_state);
        $this->assertEquals('upcoming', $result->stages[3]->presentation_state);
        $this->assertEquals('upcoming', $result->stages[4]->presentation_state);
    }

    public function test_completed_state()
    {
        [$workflow, $stages, $kaizen, $instance] = $this->setupWorkflowAndInstance(3);
        $kaizen->status = KaizenStatus::APPROVED;
        $kaizen->save();
        $instance->update([
            'current_stage_id' => $stages[2]->id,
            'completed_at' => now(),
        ]);

        $result = $this->presenter->present($kaizen);

        foreach ($result->stages as $stage) {
            $this->assertEquals('completed', $stage->presentation_state);
        }
    }

    public function test_rejected_state()
    {
        [$workflow, $stages, $kaizen, $instance] = $this->setupWorkflowAndInstance(3);
        $kaizen->status = KaizenStatus::REJECTED;
        $kaizen->save();
        $instance->update([
            'current_stage_id' => $stages[1]->id,
            'cancelled_at' => now(),
        ]);

        $result = $this->presenter->present($kaizen);

        $this->assertEquals('completed', $result->stages[0]->presentation_state);
        $this->assertEquals('rejected', $result->stages[1]->presentation_state);
        $this->assertEquals('upcoming', $result->stages[2]->presentation_state);
    }

    public function test_revision_state()
    {
        [$workflow, $stages, $kaizen, $instance] = $this->setupWorkflowAndInstance(3);
        $kaizen->status = KaizenStatus::REVISION_REQUESTED;
        $kaizen->save();
        $instance->update(['current_stage_id' => $stages[1]->id]);

        $result = $this->presenter->present($kaizen);

        $this->assertEquals('completed', $result->stages[0]->presentation_state);
        $this->assertEquals('revision', $result->stages[1]->presentation_state);
        $this->assertEquals('upcoming', $result->stages[2]->presentation_state);
    }

    public function test_eight_stages_support()
    {
        [$workflow, $stages, $kaizen, $instance] = $this->setupWorkflowAndInstance(8);
        $result = $this->presenter->present($kaizen);
        $this->assertCount(8, $result->stages);
    }

    public function test_history_presentation()
    {
        [$workflow, $stages, $kaizen, $instance] = $this->setupWorkflowAndInstance(3);
        $actor = User::factory()->create(['name' => 'John Doe']);

        $instance->transitions()->create([
            'kaizen_id' => $kaizen->id,
            'from_stage_id' => null,
            'to_stage_id' => $stages[0]->id,
            'actor_user_id' => $actor->id,
            'action' => WorkflowAction::START,
            'comment' => null,
            'created_at' => now()->subMinutes(10),
        ]);

        $instance->transitions()->create([
            'kaizen_id' => $kaizen->id,
            'from_stage_id' => $stages[0]->id,
            'to_stage_id' => $stages[1]->id,
            'actor_user_id' => $actor->id,
            'action' => WorkflowAction::APPROVE,
            'comment' => 'Looks good',
            'created_at' => now()->subMinutes(5),
        ]);

        $result = $this->presenter->present($kaizen);

        $this->assertCount(2, $result->history);

        // History is descending
        $approveAction = $result->history[0];
        $startAction = $result->history[1];

        $this->assertEquals('Onaylandı', $approveAction->actionLabel);
        $this->assertEquals('Stage 1 aşaması onaylandı, Stage 2 aşamasına geçildi.', $approveAction->stageContext);
        $this->assertEquals('John Doe', $approveAction->actorName);
        $this->assertEquals('Looks good', $approveAction->comment);

        $this->assertEquals('Süreç Başlatıldı', $startAction->actionLabel);
        $this->assertEquals('Stage 1 aşamasına gönderildi.', $startAction->stageContext);
    }
}
