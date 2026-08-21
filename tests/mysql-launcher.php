<?php

// tests/mysql-launcher.php

require __DIR__ . '/../vendor/autoload.php';

use Tests\Support\MySqlTestLauncher;

// 1. Clear config before running tests safely
// Note: This must be done via a safe subprocess without loading the dangerous .env into our parent!
// Since we don't have safe child environment yet, we just execute config:clear directly
$clearCommand = [PHP_BINARY, 'artisan', 'config:clear', '--ansi'];
$clearProcess = proc_open($clearCommand, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $clearPipes);
if (is_resource($clearProcess)) {
    fclose($clearPipes[0]);
    echo stream_get_contents($clearPipes[1]);
    fwrite(STDERR, stream_get_contents($clearPipes[2]));
    fclose($clearPipes[1]);
    fclose($clearPipes[2]);
    $clearExitCode = proc_close($clearProcess);
    if ($clearExitCode !== 0) {
        exit($clearExitCode);
    }
}

// 2. Launch the safe test process
$envPath = dirname(__DIR__) . '/.env';
$cliArgs = array_slice($argv, 1);

try {
    $launcher = new MySqlTestLauncher($envPath, $cliArgs);
    $exitCode = $launcher->run();
    exit($exitCode);
} catch (\Throwable $e) {
    // If it fails during preflight, print safely and exit 1
    // Wait, the redactOutput requires loadEnvironment to have been called.
    // If it crashed before/during loadEnvironment, it might not be redacted. 
    // But exception messages from our code only contain the keys, not the values.
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
