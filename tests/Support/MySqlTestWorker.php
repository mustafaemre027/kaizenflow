<?php

use App\Actions\ApprovalConfiguration\CreateApprovalWorkflowDraft;
use App\Actions\ApprovalConfiguration\MutateApprovalStageApproverRule;
use App\Actions\ApprovalConfiguration\PublishApprovalWorkflow;
use App\Actions\ApprovalConfiguration\SetDefaultApprovalWorkflow;
use App\Actions\Users\CreateUserWithInvitation;
use App\Actions\Workflow\ProgressKaizenWorkflow;
use App\Enums\ApprovalApproverScopeSource;
use App\Enums\UserCapability;
use App\Enums\WorkflowAction;
use App\Exceptions\AuthorizationException;
use App\Models\ApprovalStage;
use App\Models\ApprovalWorkflow;
use App\Models\KaizenWorkflowInstance;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

// tests/Support/MySqlTestWorker.php
if (getenv('APP_ENV') !== 'testing' || getenv('DB_CONNECTION') !== 'mysql' || getenv('DB_DATABASE') !== 'kaizenflow_test') {
    echo "ERROR: Missing secure environment constraints.\n";
    exit(1);
}

require __DIR__.'/../../vendor/autoload.php';
$app = require_once __DIR__.'/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$db = DB::select('SELECT DATABASE() as db')[0]->db;
if ($db !== 'kaizenflow_test') {
    echo 'FATAL: Canary detected wrong database: '.$db."\n";
    exit(1);
}

if (in_array('--db-check', $argv)) {
    echo "DATABASE:kaizenflow_test\n";
    exit(0);
}

if (in_array('--canary', $argv)) {
    try {
        DB::transaction(function () {
            DB::table('approval_workflows')->insert([
                'name' => 'Canary', 'code' => 'CANARY', 'version' => 999, 'is_active' => false, 'is_default' => false,
            ]);
            throw new Exception('ROLLBACK');
        });
    } catch (Exception $e) {
        if ($e->getMessage() !== 'ROLLBACK') {
            echo 'ERROR: '.$e->getMessage();
            exit(1);
        }
    }
    echo "CANARY_SUCCESS\n";
    exit(0);
}

if (in_array('--race-worker', $argv)) {
    $raceType = null;
    $barrierDir = null;
    $workerId = null;
    $payload = null;

    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--race-type=')) {
            $raceType = substr($arg, 12);
        }
        if (str_starts_with($arg, '--barrier-dir=')) {
            $barrierDir = substr($arg, 14);
        }
        if (str_starts_with($arg, '--worker-id=')) {
            $workerId = substr($arg, 12);
        }
        if (str_starts_with($arg, '--payload=')) {
            $payload = json_decode(substr($arg, 10), true);
        }
    }

    if (! $raceType || ! $barrierDir || ! $workerId) {
        echo "ERROR: Missing race worker arguments\n";
        exit(1);
    }

    // Signal ready
    file_put_contents($barrierDir.'/'.$workerId.'.ready', 'ready');

    // Wait for go signal
    $goFile = $barrierDir.'/release.go';
    $waited = 0;
    while (! file_exists($goFile)) {
        if ($waited > 100) { // 10 seconds
            echo "ERROR: Timeout waiting for release\n";
            exit(1);
        }
        usleep(100000); // 100ms
        $waited++;
    }

    try {
        if ($raceType === 'A' || $raceType === 'C') {
            $actor = User::findOrFail($payload['user_id']);
            $action = $app->make(CreateApprovalWorkflowDraft::class);
            $action->execute(
                $actor,
                $payload['code'],
                $payload['name'],
                $payload['description'] ?? null,
                $payload['stages']
            );
            echo "STATUS:SUCCESS\n";
        } elseif ($raceType === 'B') {
            $actor = User::findOrFail($payload['user_id']);
            $target = ApprovalWorkflow::findOrFail($payload['workflow_id']);
            $action = $app->make(SetDefaultApprovalWorkflow::class);
            $action->execute($actor, $target);
            echo "STATUS:SUCCESS\n";
        } elseif ($raceType === 'RULE_A') {
            $actor = User::findOrFail($payload['user_id']);
            $stage = ApprovalStage::findOrFail($payload['stage_id']);
            $capability = UserCapability::from($payload['capability']);
            $scopeSource = ApprovalApproverScopeSource::from($payload['scope_source']);
            $action = $app->make(MutateApprovalStageApproverRule::class);
            $action->execute($actor, $stage, $capability, $scopeSource, true);
            echo "STATUS:SUCCESS\n";
        } elseif ($raceType === 'RULE_B_MUTATE') {
            $actor = User::findOrFail($payload['user_id']);
            $stage = ApprovalStage::findOrFail($payload['stage_id']);
            $capability = UserCapability::from($payload['capability']);
            $scopeSource = ApprovalApproverScopeSource::from($payload['scope_source']);
            $action = $app->make(MutateApprovalStageApproverRule::class);
            $action->execute($actor, $stage, $capability, $scopeSource, false);
            echo "STATUS:SUCCESS\n";
        } elseif ($raceType === 'RULE_B_PUBLISH') {
            $actor = User::findOrFail($payload['user_id']);
            $target = ApprovalWorkflow::findOrFail($payload['workflow_id']);
            $action = $app->make(PublishApprovalWorkflow::class);
            $action->execute($actor, $target);
            echo "STATUS:SUCCESS\n";
        } elseif ($raceType === 'RULE_C') {
            $actor = User::findOrFail($payload['user_id']);
            $instance = KaizenWorkflowInstance::findOrFail($payload['instance_id']);
            $action = $app->make(ProgressKaizenWorkflow::class);
            $action->execute($instance->kaizen, $actor, WorkflowAction::APPROVE, 'concurrent test');
            echo "STATUS:SUCCESS\n";
        } elseif ($raceType === 'CREATE_USER_RACE') {
            $actor = User::findOrFail($payload['user_id']);
            $action = $app->make(CreateUserWithInvitation::class);
            $action->execute($actor, $payload['validated']);
            echo "STATUS:SUCCESS\n";
        }
    } catch (AuthorizationException $e) {
        echo "STATUS:REJECTED\n";
    } catch (\App\Exceptions\DomainException $e) {
        echo "STATUS:REJECTED\n";
    } catch (\DomainException $e) {
        echo "STATUS:REJECTED\n";
    } catch (Throwable $e) {
        echo 'STATUS:ERROR '.get_class($e)."\n";
    }

    exit(0);
}
