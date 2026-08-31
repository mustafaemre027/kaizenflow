<?php

namespace Tests\Feature\Users;

use App\Enums\UserCapability;
use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use Illuminate\Support\Facades\DB;
use Tests\Support\RaceHarness;
use Tests\TestCase;

class CreateUserRaceTest extends TestCase
{
    private ?RaceHarness $harness = null;

    private array $createdUserIds = [];

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
        if (\config('database.default') !== 'mysql') {
            parent::tearDown();

            return;
        }

        if ($this->harness) {
            $this->harness->cleanup();
        }

        // Cleanup created test users
        if (! empty($this->createdUserIds)) {
            DB::table('audit_logs')->whereIn('actor_user_id', $this->createdUserIds)->delete();
            DB::table('user_system_capability_grants')->whereIn('user_id', $this->createdUserIds)->delete();
            DB::table('users')->whereIn('id', $this->createdUserIds)->delete();
            DB::table('users')->where('email', 'race_create_concurrent@example.com')->delete();
        }

        parent::tearDown();
    }

    public function test_concurrent_create_duplicate_email_race()
    {
        $actor = User::factory()->create();
        $this->createdUserIds[] = $actor->id;

        UserSystemCapabilityGrant::create([
            'user_id' => $actor->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
            'is_active' => true,
        ]);

        $payload = [
            'user_id' => $actor->id,
            'validated' => [
                'name' => 'Concurrent User',
                'email' => 'race_create_concurrent@example.com',
                'role' => UserRole::EMPLOYEE->value,
                'department_id' => null,
            ],
        ];

        $w1 = $this->harness->spawnWorker('CREATE_USER_RACE', 'w1', $payload);
        $w2 = $this->harness->spawnWorker('CREATE_USER_RACE', 'w2', $payload);

        $this->harness->waitForReady([$w1, $w2]);
        $this->harness->releaseWorkers();

        $results = $this->harness->collectResults([$w1, $w2]);

        $successCount = 0;
        $rejectedCount = 0;
        $allStdout = '';

        foreach ($results as $res) {
            $allStdout .= "Worker {$res['id']}: {$res['stdout']}\n";
            $this->assertEquals(0, $res['exitcode'], "Worker {$res['id']} failed: ".$res['stdout'].$res['stderr']);

            if (str_contains($res['stdout'], 'STATUS:SUCCESS')) {
                $successCount++;
            } elseif (str_contains($res['stdout'], 'STATUS:REJECTED')) {
                $rejectedCount++;
            }
        }

        $this->assertEquals(1, $successCount, 'Only one worker should succeed. Output: '.$allStdout);
        $this->assertEquals(1, $rejectedCount, 'One worker should be rejected gracefully. Output: '.$allStdout);

        $usersCount = User::where('email', 'race_create_concurrent@example.com')->count();
        $this->assertEquals(1, $usersCount, 'Only one user should be created in the database.');
    }
}
