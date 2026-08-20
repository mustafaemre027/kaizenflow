<?php

namespace Tests\Unit\Workflow;

use App\Exceptions\Workflow\InvalidApprovalWorkflowConfiguration;
use App\Models\ApprovalStage;
use App\Models\ApprovalWorkflow;
use App\Services\Workflow\ApprovalWorkflowResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalWorkflowResolverTest extends TestCase
{
    use RefreshDatabase;

    private ApprovalWorkflowResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new ApprovalWorkflowResolver;
    }

    public function test_it_resolves_the_default_active_published_workflow()
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

        $resolved = $this->resolver->resolveDefaultForNewInstance();

        $this->assertTrue($resolved->is($workflow));
    }

    public function test_it_fails_if_no_default_workflow_exists()
    {
        ApprovalWorkflow::factory()->create([
            'is_default' => false,
            'is_active' => true,
            'published_at' => now(),
        ]);

        $this->expectException(InvalidApprovalWorkflowConfiguration::class);
        $this->expectExceptionMessage('No default active published workflow could be found.');

        $this->resolver->resolveDefaultForNewInstance();
    }

    public function test_it_fails_if_multiple_defaults_exist()
    {
        ApprovalWorkflow::factory()->count(2)->create([
            'is_default' => true,
            'is_active' => true,
            'published_at' => now(),
        ]);

        $this->expectException(InvalidApprovalWorkflowConfiguration::class);
        $this->expectExceptionMessage('Multiple default active published workflows were found.');

        $this->resolver->resolveDefaultForNewInstance();
    }

    public function test_it_ignores_draft_and_inactive_workflows()
    {
        // Draft
        ApprovalWorkflow::factory()->create([
            'is_default' => true,
            'is_active' => true,
            'published_at' => null,
        ]);

        // Inactive
        ApprovalWorkflow::factory()->create([
            'is_default' => true,
            'is_active' => false,
            'published_at' => now(),
        ]);

        $this->expectException(InvalidApprovalWorkflowConfiguration::class);

        $this->resolver->resolveDefaultForNewInstance();
    }

    public function test_it_fails_if_workflow_has_no_active_stages()
    {
        $workflow = ApprovalWorkflow::factory()->create([
            'is_default' => true,
            'is_active' => true,
            'published_at' => now(),
        ]);

        ApprovalStage::factory()->create([
            'approval_workflow_id' => $workflow->id,
            'is_active' => false,
            'is_final' => true,
        ]);

        $this->expectException(InvalidApprovalWorkflowConfiguration::class);
        $this->expectExceptionMessage('The workflow has no active stages configured.');

        $this->resolver->resolveDefaultForNewInstance();
    }
}
