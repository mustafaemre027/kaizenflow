<?php

// tests/mysql-launcher.php
$env = [];
$whitelist = ['PATH', 'SystemRoot', 'SystemDrive', 'TEMP', 'TMP', 'DB_USERNAME', 'DB_PASSWORD', 'DB_HOST', 'DB_PORT'];
foreach ($whitelist as $k) {
    // Read from both getenv and $_SERVER to be safe
    $val = getenv($k);
    if ($val === false && isset($_SERVER[$k])) {
        $val = $_SERVER[$k];
    }
    if ($val !== false) {
        $env[$k] = $val;
    }
}

// Ensure credentials aren't missing (read .env if needed)
if (! isset($env['DB_USERNAME'])) {
    // Fallback to loading .env directly if we are running from CLI
    $envLines = file(__DIR__.'/../.env');
    foreach ($envLines as $line) {
        if (strpos($line, 'DB_USERNAME=') === 0) {
            $env['DB_USERNAME'] = trim(explode('=', $line, 2)[1]);
        }
        if (strpos($line, 'DB_PASSWORD=') === 0) {
            $env['DB_PASSWORD'] = trim(explode('=', $line, 2)[1]);
        }
        if (strpos($line, 'DB_HOST=') === 0) {
            $env['DB_HOST'] = trim(explode('=', $line, 2)[1]);
        }
        if (strpos($line, 'DB_PORT=') === 0) {
            $env['DB_PORT'] = trim(explode('=', $line, 2)[1]);
        }
    }
}

// Force the child to use kaizenflow_test unconditionally
$env['APP_ENV'] = 'testing';
$env['DB_CONNECTION'] = 'mysql';
$env['DB_DATABASE'] = 'kaizenflow_test';

$workerPath = __DIR__.'/Support/MySqlTestWorker.php';

if (in_array('--db-check', $argv)) {
    $cmd = [PHP_BINARY, escapeshellarg($workerPath), '--db-check'];
} else {
    // Pass args safely
    $cmd = [PHP_BINARY, escapeshellarg($workerPath)];
    foreach (array_slice($argv, 1) as $arg) {
        $cmd[] = escapeshellarg($arg);
    }
}

$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];

$process = proc_open(implode(' ', $cmd), $descriptors, $pipes, null, $env);
$output = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$status = proc_get_status($process);
proc_close($process);

echo $output;
if ($stderr) {
    echo $stderr;
}
exit($status['exitcode']);
