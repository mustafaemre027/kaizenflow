<?php

namespace Tests\Feature;

use App\Actions\ApprovalConfiguration\CreateApprovalWorkflowDraft;
use App\Actions\ApprovalConfiguration\DeactivateApprovalWorkflow;
use App\Actions\ApprovalConfiguration\PublishApprovalWorkflow;
use App\Actions\ApprovalConfiguration\SetDefaultApprovalWorkflow;
use App\Actions\ApprovalConfiguration\UpdateApprovalWorkflowDraft;
use App\Enums\UserCapability;
use App\Exceptions\AuthorizationException;
use App\Exceptions\DomainException;
use App\Models\ApprovalStage;
use App\Models\ApprovalWorkflow;
use App\Models\AuditLog;
use App\Models\KaizenWorkflowInstance;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalConfigurationMutationTest extends TestCase
{
    use RefreshDatabase;

    private User $authorizedUser;

    private User $unauthorizedUser;

    private User $inactiveUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authorizedUser = User::factory()->create(['is_active' => true]);
        $this->unauthorizedUser = User::factory()->create(['is_active' => true]);
        $this->inactiveUser = User::factory()->create(['is_active' => false]);

        UserSystemCapabilityGrant::create([
            'user_id' => $this->authorizedUser->id,
            'capability' => UserCapability::APPROVAL_CONFIGURATION_MANAGE,
            'is_active' => true,
        ]);
    }

    public function test_create_requires_active_authorized_user()
    {
        $action = $this->app->make(CreateApprovalWorkflowDraft::class);

        $this->expectException(AuthorizationException::class);
        $action->execute($this->unauthorizedUser, 'CODE1', 'Name', null, []);
    }

    public function test_create_workflow_creates_first_version_with_stages_and_audit()
    {
        $action = $this->app->make(CreateApprovalWorkflowDraft::class);

        $workflow = $action->execute($this->authorizedUser, 'NEW_WF', 'New Workflow', 'Desc', [
            ['code' => 'STG_1', 'name' => 'Stage 1', 'description' => null, 'sequence' => 1, 'is_final' => false],
            ['code' => 'STG_2', 'name' => 'Stage 2', 'description' => null, 'sequence' => 2, 'is_final' => true],
        ]);

        $this->assertEquals(1, $workflow->version);
        $this->assertEquals('NEW_WF', $workflow->code);
        $this->assertFalse($workflow->is_active);
        $this->assertFalse($workflow->is_default);
        $this->assertNull($workflow->published_at);
        $this->assertCount(2, $workflow->stages);

        $audit = AuditLog::where('auditable_type', $workflow->getMorphClass())->where('auditable_id', $workflow->id)->first();
        $this->assertNotNull($audit);
        $this->assertEquals('approval_configuration.created', $audit->event);
        $this->assertEquals($this->authorizedUser->id, $audit->actor_user_id);
    }

    public function test_create_workflow_next_version()
    {
        ApprovalWorkflow::factory()->create(['code' => 'WF_V', 'version' => 1]);
        ApprovalWorkflow::factory()->create(['code' => 'WF_V', 'version' => 5]);

        $action = $this->app->make(CreateApprovalWorkflowDraft::class);
        $workflow = $action->execute($this->authorizedUser, 'WF_V', 'Workflow V', null, []);

        $this->assertEquals(6, $workflow->version);
    }

    public function test_update_draft_workflow()
    {
        $workflow = ApprovalWorkflow::factory()->create(['code' => 'DRAFT', 'version' => 1, 'published_at' => null, 'is_active' => false]);
        $stage1 = ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id, 'code' => 'S1', 'sequence' => 1]);

        $action = $this->app->make(UpdateApprovalWorkflowDraft::class);

        $action->execute($this->authorizedUser, $workflow, 'Updated Name', 'Desc', [
            ['id' => $stage1->id, 'code' => 'S1', 'name' => 'Updated S1', 'sequence' => 1, 'is_final' => false],
            ['code' => 'S2', 'name' => 'New S2', 'sequence' => 2, 'is_final' => true],
        ]);

        $workflow->refresh();
        $this->assertEquals('Updated Name', $workflow->name);
        $this->assertCount(2, $workflow->stages()->where('is_active', true)->get());

        $audit = AuditLog::where('event', 'approval_configuration.updated')->first();
        $this->assertNotNull($audit);
    }

    public function test_update_published_workflow_fails()
    {
        $workflow = ApprovalWorkflow::factory()->create(['code' => 'PUB', 'version' => 1, 'published_at' => now(), 'is_active' => true]);

        $action = $this->app->make(UpdateApprovalWorkflowDraft::class);

        $this->expectException(DomainException::class);
        $action->execute($this->authorizedUser, $workflow, 'Name', null, []);
    }

    public function test_publish_workflow()
    {
        $workflow = ApprovalWorkflow::factory()->create(['published_at' => null, 'is_active' => false]);
        ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id, 'is_final' => true, 'sequence' => 1]);

        $action = $this->app->make(PublishApprovalWorkflow::class);
        $action->execute($this->authorizedUser, $workflow);

        $workflow->refresh();
        $this->assertNotNull($workflow->published_at);
        $this->assertTrue($workflow->is_active);

        $audit = AuditLog::where('event', 'approval_configuration.published')->first();
        $this->assertNotNull($audit);
    }

    public function test_publish_workflow_fails_without_final_stage()
    {
        $workflow = ApprovalWorkflow::factory()->create(['published_at' => null, 'is_active' => false]);
        ApprovalStage::factory()->create(['approval_workflow_id' => $workflow->id, 'is_final' => false, 'sequence' => 1]);

        $action = $this->app->make(PublishApprovalWorkflow::class);
        $this->expectException(DomainException::class);
        $action->execute($this->authorizedUser, $workflow);
    }

    public function test_set_default_workflow()
    {
        $oldDefault = ApprovalWorkflow::factory()->create(['is_default' => true, 'published_at' => now(), 'is_active' => true]);
        $newDefault = ApprovalWorkflow::factory()->create(['is_default' => false, 'published_at' => now(), 'is_active' => true]);

        $action = $this->app->make(SetDefaultApprovalWorkflow::class);
        $action->execute($this->authorizedUser, $newDefault);

        $this->assertFalse($oldDefault->refresh()->is_default);
        $this->assertTrue($newDefault->refresh()->is_default);

        $audit = AuditLog::where('event', 'approval_configuration.default_set')->first();
        $this->assertNotNull($audit);
    }

    public function test_deactivate_workflow()
    {
        $workflow = ApprovalWorkflow::factory()->create(['is_default' => false, 'published_at' => now(), 'is_active' => true]);

        $action = $this->app->make(DeactivateApprovalWorkflow::class);
        $action->execute($this->authorizedUser, $workflow);

        $this->assertFalse($workflow->refresh()->is_active);

        $audit = AuditLog::where('event', 'approval_configuration.deactivated')->first();
        $this->assertNotNull($audit);
    }

    public function test_deactivate_default_workflow_fails()
    {
        $workflow = ApprovalWorkflow::factory()->create(['is_default' => true, 'published_at' => now(), 'is_active' => true]);

        $action = $this->app->make(DeactivateApprovalWorkflow::class);
        $this->expectException(DomainException::class);
        $action->execute($this->authorizedUser, $workflow);
    }

    public function test_deactivate_workflow_with_active_instances_fails()
    {
        $workflow = ApprovalWorkflow::factory()->create(['is_default' => false, 'published_at' => now(), 'is_active' => true]);
        // Make sure it doesn't fail due to FKs
        KaizenWorkflowInstance::factory()->create([
            'approval_workflow_id' => $workflow->id,
            'completed_at' => null,
            'cancelled_at' => null,
        ]);

        $action = $this->app->make(DeactivateApprovalWorkflow::class);
        $this->expectException(DomainException::class);
        $action->execute($this->authorizedUser, $workflow);
    }
}
