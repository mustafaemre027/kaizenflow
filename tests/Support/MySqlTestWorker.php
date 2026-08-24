<?php

use App\Actions\ApprovalConfiguration\CreateApprovalWorkflowDraft;
use App\Actions\ApprovalConfiguration\SetDefaultApprovalWorkflow;
use App\Exceptions\AuthorizationException;
use App\Exceptions\DomainException;
use App\Models\ApprovalWorkflow;
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
        }
    } catch (AuthorizationException $e) {
        echo "STATUS:REJECTED\n";
    } catch (DomainException $e) {
        echo "STATUS:REJECTED\n";
    } catch (Throwable $e) {
        echo 'STATUS:ERROR '.get_class($e)."\n";
    }

    exit(0);
}
