<?php

namespace Tests\Unit\Workflow;

use App\Models\ApprovalStage;
use App\Models\ApprovalWorkflow;
use App\Services\Workflow\ApprovalWorkflowNavigator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalWorkflowNavigatorTest extends TestCase
{
    use RefreshDatabase;

    private ApprovalWorkflowNavigator $navigator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->navigator = new ApprovalWorkflowNavigator;
    }

    public function test_it_returns_the_first_active_stage()
    {
        $workflow = ApprovalWorkflow::factory()->create();

        $stage30 = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id, 'sequence' => 30]);
        $stage10 = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id, 'sequence' => 10]);
        $stage20 = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id, 'sequence' => 20]);

        $first = $this->navigator->firstStage($workflow);

        $this->assertTrue($first->is($stage10));
    }

    public function test_it_navigates_arbitrary_sequences_correctly()
    {
        $workflow = ApprovalWorkflow::factory()->create();

        $stage10 = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id, 'sequence' => 10]);
        $stage20 = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id, 'sequence' => 20]);
        $stage40 = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id, 'sequence' => 40]);
        $stage90 = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id, 'sequence' => 90]);
        $stage120 = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id, 'sequence' => 120, 'is_final' => true]);

        $this->assertTrue($this->navigator->nextStage($stage10)->is($stage20));
        $this->assertTrue($this->navigator->nextStage($stage20)->is($stage40));
        $this->assertTrue($this->navigator->nextStage($stage40)->is($stage90));
        $this->assertTrue($this->navigator->nextStage($stage90)->is($stage120));

        $this->assertNull($this->navigator->nextStage($stage120));
    }

    public function test_it_skips_inactive_stages()
    {
        $workflow = ApprovalWorkflow::factory()->create();

        $stage10 = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id, 'sequence' => 10]);
        $stage20 = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id, 'sequence' => 20, 'is_active' => false]);
        $stage30 = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id, 'sequence' => 30]);

        $next = $this->navigator->nextStage($stage10);

        $this->assertTrue($next->is($stage30));
    }
}
