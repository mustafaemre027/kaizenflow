<?php

namespace Tests\Feature\Workflow;

use App\Enums\ApprovalApproverScopeSource;
use App\Enums\ApproverResolutionMode;
use App\Enums\UserCapability;
use App\Enums\UserRole;
use App\Enums\WorkflowAction;
use App\Models\ApprovalStage;
use App\Models\ApprovalStageApproverRule;
use App\Models\ApprovalWorkflow;
use App\Models\Kaizen;
use App\Models\KaizenWorkflowInstance;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use Illuminate\Support\Facades\DB;
use Tests\Support\RaceHarness;
use Tests\TestCase;

class DynamicApproverConcurrencyTest extends TestCase
{
    private array $createdUserIds = [];
    private array $createdWorkflowCodes = [];
    private ?RaceHarness $harness = null;

    protected function setUp(): void
    {
        parent::setUp();
        if (env('DB_CONNECTION') !== 'mysql') {
            $this->markTestSkipped('Concurrency tests require MySQL.');
        }

        $this->harness = new RaceHarness;
    }

    protected function tearDown(): void
    {
        if (env('DB_CONNECTION') !== 'mysql') {
            parent::tearDown();
            return;
        }

        if ($this->harness) {
            $this->harness->cleanup();
        }

        DB::table('audit_logs')
            ->where('event', 'like', 'approval_configuration.%')
            ->orWhere('event', 'like', 'kaizen.%')
            ->delete();

        foreach ($this->createdWorkflowCodes as $code) {
            DB::table('kaizen_workflow_transitions')->whereIn('kaizen_workflow_instance_id', function ($query) use ($code) {
                $query->select('id')->from('kaizen_workflow_instances')->whereIn('approval_workflow_id', function ($q) use ($code) {
                    $q->select('id')->from('approval_workflows')->where('code', $code);
                });
            })->delete();

            DB::table('kaizen_workflow_instances')->whereIn('approval_workflow_id', function ($query) use ($code) {
                $query->select('id')->from('approval_workflows')->where('code', $code);
            })->delete();
            DB::table('approval_stage_approver_rules')->whereIn('approval_stage_id', function ($query) use ($code) {
                $query->select('id')->from('approval_stages')->whereIn('approval_workflow_id', function ($q) use ($code) {
                    $q->select('id')->from('approval_workflows')->where('code', $code);
                });
            })->delete();
            DB::table('approval_stages')->whereIn('approval_workflow_id', function ($query) use ($code) {
                $query->select('id')->from('approval_workflows')->where('code', $code);
            })->delete();
            DB::table('approval_workflows')->where('code', $code)->delete();
        }
        
        DB::table('kaizens')->whereIn('creator_user_id', $this->createdUserIds)->delete();

        foreach ($this->createdUserIds as $userId) {
            DB::table('user_system_capability_grants')->where('user_id', $userId)->delete();
            DB::table('users')->where('id', $userId)->delete();
        }

        parent::tearDown();
    }

    public function test_race_a_concurrent_rule_mutation()
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->createdUserIds[] = $admin->id;

        UserSystemCapabilityGrant::create([
            'user_id' => $admin->id,
            'capability' => UserCapability::APPROVAL_CONFIGURATION_MANAGE,
            'is_active' => true,
        ]);

        $code = 'RULE_RACE_A_'.uniqid();
        $this->createdWorkflowCodes[] = $code;

        $wf = ApprovalWorkflow::create([
            'code' => $code,
            'name' => 'WF',
            'version' => 1,
            'approver_resolution_mode' => ApproverResolutionMode::CAPABILITY_RULE,
            'is_active' => true,
        ]);
        $stage = ApprovalStage::create([
            'approval_workflow_id' => $wf->id,
            'code' => 'S1',
            'name' => 'S1',
            'sequence' => 1,
            'is_final' => true,
            'is_active' => true,
        ]);

        $payload1 = [
            'user_id' => $admin->id,
            'workflow_id' => $wf->id,
            'stage_id' => $stage->id,
            'capability' => UserCapability::KAIZEN_BOARD_APPROVE->value,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM->value,
        ];
        
        $payload2 = [
            'user_id' => $admin->id,
            'workflow_id' => $wf->id,
            'stage_id' => $stage->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW->value,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM->value,
        ];

        $w1 = $this->harness->spawnWorker('RULE_A', 'w1', $payload1);
        $w2 = $this->harness->spawnWorker('RULE_A', 'w2', $payload2);

        $this->harness->waitForReady([$w1, $w2]);
        $this->harness->releaseWorkers();

        $results = $this->harness->collectResults([$w1, $w2]);

        foreach ($results as $res) {
            $this->assertEquals(0, $res['exitcode'], "Worker {$res['id']} failed: ".$res['stdout'].$res['stderr']);
            $this->assertStringContainsString('STATUS:SUCCESS', $res['stdout']);
        }

        $rules = ApprovalStageApproverRule::where('approval_stage_id', $stage->id)->get();
        $this->assertCount(1, $rules, "Only exactly 1 rule must exist per stage");
        
        $audits = DB::table('audit_logs')
            ->where('event', 'approval_configuration.approver_rule_updated')
            ->where('actor_user_id', $admin->id)
            ->get();
        // Since they mutate it sequentially (one wins the lock, then the other gets the lock and overwrites), there should be 2 audit logs.
        $this->assertCount(2, $audits);
    }

    public function test_race_b_publish_and_rule_passivation_race()
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->createdUserIds[] = $admin->id;

        UserSystemCapabilityGrant::create([
            'user_id' => $admin->id,
            'capability' => UserCapability::APPROVAL_CONFIGURATION_MANAGE,
            'is_active' => true,
        ]);

        $code = 'RULE_RACE_B_'.uniqid();
        $this->createdWorkflowCodes[] = $code;

        $wf = ApprovalWorkflow::create([
            'code' => $code,
            'name' => 'WF',
            'version' => 1,
            'approver_resolution_mode' => ApproverResolutionMode::CAPABILITY_RULE,
            'is_active' => true,
        ]);
        $stage = ApprovalStage::create([
            'approval_workflow_id' => $wf->id,
            'code' => 'S1',
            'name' => 'S1',
            'sequence' => 1,
            'is_final' => true,
            'is_active' => true,
        ]);
        ApprovalStageApproverRule::create([
            'approval_stage_id' => $stage->id,
            'capability' => UserCapability::KAIZEN_BOARD_APPROVE,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM,
            'is_active' => true,
        ]);

        // Worker 1 tries to deactivate rule
        $payloadDeactivate = [
            'user_id' => $admin->id,
            'workflow_id' => $wf->id,
            'stage_id' => $stage->id,
            'capability' => UserCapability::KAIZEN_BOARD_APPROVE->value,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM->value,
        ];
        
        // Worker 2 tries to publish workflow
        $payloadPublish = [
            'user_id' => $admin->id,
            'workflow_id' => $wf->id,
        ];

        $w1 = $this->harness->spawnWorker('RULE_B_MUTATE', 'w1', $payloadDeactivate);
        $w2 = $this->harness->spawnWorker('RULE_B_PUBLISH', 'w2', $payloadPublish);

        $this->harness->waitForReady([$w1, $w2]);
        $this->harness->releaseWorkers();

        $results = $this->harness->collectResults([$w1, $w2]);

        $successCount = 0;
        $rejectedCount = 0;
        foreach ($results as $res) {
            $this->assertEquals(0, $res['exitcode']);
            if (str_contains($res['stdout'], 'STATUS:SUCCESS')) {
                $successCount++;
            } elseif (str_contains($res['stdout'], 'STATUS:REJECTED')) {
                $rejectedCount++;
            }
        }

        $this->assertEquals(1, $successCount, 'Exactly one transaction should succeed.');
        $this->assertEquals(1, $rejectedCount, 'Exactly one transaction should be rejected.');

        $wf->refresh();
        $rule = ApprovalStageApproverRule::where('approval_stage_id', $stage->id)->first();
        
        if ($wf->published_at !== null) {
            $this->assertTrue($rule->is_active, 'If published, rule MUST be active.');
        } else {
            $this->assertFalse($rule->is_active, 'If not published, it means rule deactivation won, so it must be inactive.');
        }
    }

    public function test_race_c_grant_revoke_and_runtime_approval()
    {
        $creator = User::factory()->create();
        $approver = User::factory()->create();
        $this->createdUserIds[] = $creator->id;
        $this->createdUserIds[] = $approver->id;

        $grant = UserSystemCapabilityGrant::create([
            'user_id' => $approver->id,
            'capability' => UserCapability::KAIZEN_BOARD_APPROVE,
            'is_active' => true,
        ]);

        $code = 'RULE_RACE_C_'.uniqid();
        $this->createdWorkflowCodes[] = $code;

        $wf = ApprovalWorkflow::create([
            'code' => $code,
            'name' => 'WF',
            'version' => 1,
            'approver_resolution_mode' => ApproverResolutionMode::CAPABILITY_RULE,
            'is_active' => true,
            'published_at' => now(),
        ]);
        $stage = ApprovalStage::create([
            'approval_workflow_id' => $wf->id,
            'code' => 'S1',
            'name' => 'S1',
            'sequence' => 1,
            'is_final' => true,
            'is_active' => true,
        ]);
        ApprovalStageApproverRule::create([
            'approval_stage_id' => $stage->id,
            'capability' => UserCapability::KAIZEN_BOARD_APPROVE,
            'scope_source' => ApprovalApproverScopeSource::SYSTEM,
            'is_active' => true,
        ]);
        
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $creator->id,
            'status' => \App\Enums\KaizenStatus::SUBMITTED,
        ]);

        $instance = new KaizenWorkflowInstance;
        $instance->forceFill([
            'kaizen_id' => $kaizen->id,
            'approval_workflow_id' => $wf->id,
            'current_stage_id' => $stage->id,
            'started_at' => now(),
        ])->save();

        $payload = [
            'user_id' => $approver->id,
            'instance_id' => $instance->id,
        ];

        $w1 = $this->harness->spawnWorker('RULE_C', 'w1', $payload);

        $this->harness->waitForReady([$w1]);

        // Revoke the capability grant from the main thread while worker waits at barrier
        $grant->update(['is_active' => false]);

        $this->harness->releaseWorkers();

        $results = $this->harness->collectResults([$w1]);
        $res = $results[0];

        $this->assertEquals(0, $res['exitcode']);
        $this->assertStringContainsString('STATUS:REJECTED', $res['stdout']);

        $instance->refresh();
        $this->assertEquals($stage->id, $instance->current_stage_id, "Stage must not advance");

        $transitions = DB::table('kaizen_workflow_transitions')->where('kaizen_workflow_instance_id', $instance->id)->count();
        $this->assertEquals(0, $transitions);
    }
}
