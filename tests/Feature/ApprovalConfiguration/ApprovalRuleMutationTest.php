<?php

namespace Tests\Feature\ApprovalConfiguration;

use App\Actions\ApprovalConfiguration\MutateApprovalStageApproverRule;
use App\Enums\ApprovalApproverScopeSource;
use App\Enums\UserCapability;
use App\Exceptions\AuthorizationException;
use App\Exceptions\DomainException;
use App\Models\ApprovalStage;
use App\Models\ApprovalStageApproverRule;
use App\Models\ApprovalWorkflow;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use App\Services\AppendAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalRuleMutationTest extends TestCase
{
    use RefreshDatabase;

    private User $authorizedUser;

    private User $unauthorizedUser;

    private User $inactiveUser;

    private ApprovalWorkflow $workflow;

    private ApprovalStage $stage;

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

        $this->workflow = ApprovalWorkflow::factory()->create([
            'approver_resolution_mode' => 'CAPABILITY_RULE',
            'is_active' => false,
            'published_at' => null,
        ]);

        $this->stage = ApprovalStage::factory()->create([
            'approval_workflow_id' => $this->workflow->id,
        ]);
    }

    public function test_it_requires_authorized_active_user()
    {
        $action = $this->app->make(MutateApprovalStageApproverRule::class);

        $this->expectException(AuthorizationException::class);
        $action->execute($this->unauthorizedUser, $this->stage, UserCapability::KAIZEN_OPEX_REVIEW, ApprovalApproverScopeSource::SYSTEM, true);
    }

    public function test_it_rejects_inactive_user()
    {
        $action = $this->app->make(MutateApprovalStageApproverRule::class);

        $this->expectException(AuthorizationException::class);
        $action->execute($this->inactiveUser, $this->stage, UserCapability::KAIZEN_OPEX_REVIEW, ApprovalApproverScopeSource::SYSTEM, true);
    }

    public function test_it_rejects_mutation_on_published_workflow()
    {
        $this->workflow->update(['published_at' => now()]);

        $action = $this->app->make(MutateApprovalStageApproverRule::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot mutate rules for a published workflow.');
        $action->execute($this->authorizedUser, $this->stage, UserCapability::KAIZEN_OPEX_REVIEW, ApprovalApproverScopeSource::SYSTEM, true);
    }

    public function test_it_rejects_mutation_on_legacy_group_workflow()
    {
        $this->workflow->update(['approver_resolution_mode' => 'LEGACY_GROUP']);

        $action = $this->app->make(MutateApprovalStageApproverRule::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot set capability rules on a LEGACY_GROUP workflow.');
        $action->execute($this->authorizedUser, $this->stage, UserCapability::KAIZEN_OPEX_REVIEW, ApprovalApproverScopeSource::SYSTEM, true);
    }

    public function test_it_rejects_invalid_capability_scope_match()
    {
        $action = $this->app->make(MutateApprovalStageApproverRule::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Capability and scope mismatch.');
        $action->execute($this->authorizedUser, $this->stage, UserCapability::KAIZEN_OPEX_REVIEW, ApprovalApproverScopeSource::KAIZEN_DEPARTMENT, true);
    }

    public function test_it_creates_new_rule_and_audits()
    {
        $action = $this->app->make(MutateApprovalStageApproverRule::class);
        $action->execute($this->authorizedUser, $this->stage, UserCapability::KAIZEN_OPEX_REVIEW, ApprovalApproverScopeSource::SYSTEM, true);

        $this->assertDatabaseHas('approval_stage_approver_rules', [
            'approval_stage_id' => $this->stage->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW->value,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM->value,
            'is_active' => true,
        ]);

        $audit = AuditLog::where('event', 'approval_configuration.approver_rule_updated')->first();
        $this->assertNotNull($audit);
        $this->assertEquals($this->authorizedUser->id, $audit->actor_user_id);
    }

    public function test_it_updates_existing_rule_and_audits()
    {
        ApprovalStageApproverRule::factory()->create([
            'approval_stage_id' => $this->stage->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM,
            'is_active' => false,
        ]);

        $action = $this->app->make(MutateApprovalStageApproverRule::class);
        $action->execute($this->authorizedUser, $this->stage, UserCapability::KAIZEN_BOARD_APPROVE, ApprovalApproverScopeSource::SYSTEM, true);

        $this->assertDatabaseHas('approval_stage_approver_rules', [
            'approval_stage_id' => $this->stage->id,
            'capability' => UserCapability::KAIZEN_BOARD_APPROVE->value,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM->value,
            'is_active' => true,
        ]);

        $audit = AuditLog::where('event', 'approval_configuration.approver_rule_updated')->first();
        $this->assertNotNull($audit);
    }

    public function test_no_op_mutation_does_not_audit_or_update_timestamps()
    {
        $rule = ApprovalStageApproverRule::factory()->create([
            'approval_stage_id' => $this->stage->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM,
            'is_active' => true,
            'updated_at' => now()->subDay(),
        ]);

        $originalUpdatedAt = clone $rule->updated_at;

        $action = $this->app->make(MutateApprovalStageApproverRule::class);
        $action->execute($this->authorizedUser, $this->stage, UserCapability::KAIZEN_OPEX_REVIEW, ApprovalApproverScopeSource::SYSTEM, true);

        $rule->refresh();
        $this->assertEquals($originalUpdatedAt, $rule->updated_at);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_audit_exception_rolls_back()
    {
        $this->mock(AppendAuditLog::class, function ($mock) {
            $mock->shouldReceive('execute')->andThrow(new \Exception('Audit failed'));
        });

        $action = $this->app->make(MutateApprovalStageApproverRule::class);

        try {
            $action->execute($this->authorizedUser, $this->stage, UserCapability::KAIZEN_OPEX_REVIEW, ApprovalApproverScopeSource::SYSTEM, true);
            $this->fail('Exception expected');
        } catch (\Exception $e) {
            $this->assertEquals('Audit failed', $e->getMessage());
        }

        $this->assertDatabaseCount('approval_stage_approver_rules', 0);
    }
}
