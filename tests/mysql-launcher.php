<?php

// tests/mysql-launcher.php

// 1. Force the correct safe environment limits
putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = 'testing';
$_SERVER['APP_ENV'] = 'testing';

putenv('DB_CONNECTION=mysql');
$_ENV['DB_CONNECTION'] = 'mysql';
$_SERVER['DB_CONNECTION'] = 'mysql';

putenv('DB_DATABASE=kaizenflow_test');
$_ENV['DB_DATABASE'] = 'kaizenflow_test';
$_SERVER['DB_DATABASE'] = 'kaizenflow_test';

// 2. Load actual credentials from local .env to avoid committing them
$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            // Remove wrapping quotes if present
            if (preg_match('/^"(.*)"$/', $val, $matches) || preg_match("/^'(.*)'$/", $val, $matches)) {
                $val = $matches[1];
            }
            if (in_array($key, ['DB_HOST', 'DB_PORT', 'DB_USERNAME', 'DB_PASSWORD'])) {
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

// 3. Clear config before running tests
passthru(PHP_BINARY . ' artisan config:clear --ansi', $clearExitCode);
if ($clearExitCode !== 0) {
    exit($clearExitCode);
}

// 4. Run phpunit directly with the mysql configuration
$args = array_slice($argv, 1);
$command = PHP_BINARY . ' vendor/phpunit/phpunit/phpunit --configuration phpunit.mysql.xml ' . implode(' ', array_map('escapeshellarg', $args));

passthru($command, $exitCode);
exit($exitCode);
