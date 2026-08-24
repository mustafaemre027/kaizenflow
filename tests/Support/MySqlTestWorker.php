<?php

// tests/Support/MySqlTestWorker.php
if (getenv('APP_ENV') !== 'testing' || getenv('DB_CONNECTION') !== 'mysql' || getenv('DB_DATABASE') !== 'kaizenflow_test') {
    echo "ERROR: Missing secure environment constraints.\n";
    exit(1);
}

require __DIR__.'/../../vendor/autoload.php';
$app = require_once __DIR__.'/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

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
            // Write a dummy record to kaizenflow_test
            DB::table('approval_workflows')->insert([
                'name' => 'Canary', 'code' => 'CANARY', 'version' => 999, 'is_active' => false, 'is_default' => false,
            ]);
            // Rollback to clean up immediately
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
