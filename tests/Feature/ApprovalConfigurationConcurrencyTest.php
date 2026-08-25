<?php

namespace Tests\Feature;

use App\Enums\UserCapability;
use App\Models\ApprovalWorkflow;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use Illuminate\Support\Facades\DB;
use Tests\Support\RaceHarness;
use Tests\TestCase;

class ApprovalConfigurationConcurrencyTest extends TestCase
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

        if (! empty($this->createdWorkflowCodes)) {
            DB::table('audit_logs')
                ->where('event', 'like', 'approval_configuration.%')
                ->delete();
        }

        foreach ($this->createdWorkflowCodes as $code) {
            DB::table('kaizen_workflow_instances')->whereIn('approval_workflow_id', function ($query) use ($code) {
                $query->select('id')->from('approval_workflows')->where('code', $code);
            })->delete();
            DB::table('approval_stages')->whereIn('approval_workflow_id', function ($query) use ($code) {
                $query->select('id')->from('approval_workflows')->where('code', $code);
            })->delete();
            DB::table('approval_workflows')->where('code', $code)->delete();
        }

        foreach ($this->createdUserIds as $userId) {
            DB::table('audit_logs')->where('actor_user_id', $userId)->delete();
            DB::table('user_system_capability_grants')->where('user_id', $userId)->delete();
            DB::table('users')->where('id', $userId)->delete();
        }

        parent::tearDown();
    }

    public function test_race_a_duplicate_draft()
    {
        $user = User::factory()->create();
        $this->createdUserIds[] = $user->id;

        UserSystemCapabilityGrant::create([
            'user_id' => $user->id,
            'capability' => UserCapability::APPROVAL_CONFIGURATION_MANAGE,
            'is_active' => true,
        ]);

        $code = 'RACE_A_'.uniqid();
        $this->createdWorkflowCodes[] = $code;

        $payload = [
            'user_id' => $user->id,
            'code' => $code,
            'name' => 'Race A',
            'description' => null,
            'stages' => [
                ['code' => 'S1', 'name' => 'S1', 'sequence' => 1, 'is_final' => true],
            ],
        ];

        $w1 = $this->harness->spawnWorker('A', 'w1', $payload);
        $w2 = $this->harness->spawnWorker('A', 'w2', $payload);

        $this->harness->waitForReady([$w1, $w2]);
        $this->harness->releaseWorkers();

        $results = $this->harness->collectResults([$w1, $w2]);

        foreach ($results as $res) {
            $this->assertEquals(0, $res['exitcode'], "Worker {$res['id']} failed: ".$res['stdout'].$res['stderr']);
            $this->assertStringContainsString('STATUS:SUCCESS', $res['stdout']);
        }

        $workflows = ApprovalWorkflow::where('code', $code)->orderBy('version')->get();
        $this->assertCount(2, $workflows);
        $this->assertEquals(1, $workflows[0]->version);
        $this->assertEquals(2, $workflows[1]->version);

        $audits = DB::table('audit_logs')
            ->where('event', 'approval_configuration.created')
            ->get()
            ->filter(function ($audit) use ($code) {
                return json_decode($audit->metadata, true)['workflow_code'] === $code;
            });
        $this->assertCount(2, $audits);
    }

    public function test_race_b_concurrent_set_default()
    {
        $user = User::factory()->create();
        $this->createdUserIds[] = $user->id;

        UserSystemCapabilityGrant::create([
            'user_id' => $user->id,
            'capability' => UserCapability::APPROVAL_CONFIGURATION_MANAGE,
            'is_active' => true,
        ]);

        $code1 = 'RACE_B1_'.uniqid();
        $code2 = 'RACE_B2_'.uniqid();
        $this->createdWorkflowCodes[] = $code1;
        $this->createdWorkflowCodes[] = $code2;

        $wf1 = ApprovalWorkflow::create([
            'code' => $code1,
            'name' => 'WF1',
            'version' => 1,
            'is_active' => true,
            'is_default' => false,
            'published_at' => now(),
        ]);
        $wf2 = ApprovalWorkflow::create([
            'code' => $code2,
            'name' => 'WF2',
            'version' => 1,
            'is_active' => true,
            'is_default' => false,
            'published_at' => now(),
        ]);

        $w1 = $this->harness->spawnWorker('B', 'w1', ['user_id' => $user->id, 'workflow_id' => $wf1->id]);
        $w2 = $this->harness->spawnWorker('B', 'w2', ['user_id' => $user->id, 'workflow_id' => $wf2->id]);

        $this->harness->waitForReady([$w1, $w2]);
        $this->harness->releaseWorkers();

        $results = $this->harness->collectResults([$w1, $w2]);

        foreach ($results as $res) {
            $this->assertEquals(0, $res['exitcode'], "Worker {$res['id']} failed: ".$res['stdout'].$res['stderr']);
            $this->assertStringContainsString('STATUS:SUCCESS', $res['stdout']);
        }

        $defaultCount = ApprovalWorkflow::whereIn('code', [$code1, $code2])->where('is_default', true)->count();
        $this->assertEquals(1, $defaultCount, 'Only one workflow should be default at the end.');

        $audits = DB::table('audit_logs')
            ->where('event', 'approval_configuration.default_set')
            ->get()
            ->filter(function ($audit) use ($code1, $code2) {
                $metaCode = json_decode($audit->metadata, true)['workflow_code'] ?? null;

                return in_array($metaCode, [$code1, $code2]);
            });
        $this->assertCount(2, $audits);
    }

    public function test_race_c_stale_capability_revalidation()
    {
        $user = User::factory()->create();
        $this->createdUserIds[] = $user->id;

        $grant = UserSystemCapabilityGrant::create([
            'user_id' => $user->id,
            'capability' => UserCapability::APPROVAL_CONFIGURATION_MANAGE,
            'is_active' => true,
        ]);

        $code = 'RACE_C_'.uniqid();
        $this->createdWorkflowCodes[] = $code;

        $payload = [
            'user_id' => $user->id,
            'code' => $code,
            'name' => 'Race C',
            'description' => null,
            'stages' => [
                ['code' => 'S1', 'name' => 'S1', 'sequence' => 1, 'is_final' => true],
            ],
        ];

        $w1 = $this->harness->spawnWorker('C', 'w1', $payload);

        $this->harness->waitForReady([$w1]);

        // Revoke the capability
        $grant->update(['is_active' => false]);

        $this->harness->releaseWorkers();

        $results = $this->harness->collectResults([$w1]);
        $res = $results[0];

        $this->assertEquals(0, $res['exitcode']);
        $this->assertStringContainsString('STATUS:REJECTED', $res['stdout']);

        $workflows = ApprovalWorkflow::where('code', $code)->count();
        $this->assertEquals(0, $workflows);
    }
}
