<?php

// tests/bootstrap.php

// This is the earliest entry point for PHPUnit before Laravel is booted.
$env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? getenv('APP_ENV') ?: null;
$connection = $_ENV['DB_CONNECTION'] ?? $_SERVER['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?: null;
$database = $_ENV['DB_DATABASE'] ?? $_SERVER['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: null;

// Allow phpunit.xml attributes to take precedence if not in ENV
// In this context, if tests are run via `php artisan test`, Laravel sets these in $_ENV.
// If run via raw `phpunit`, phpunit reads phpunit.xml and sets $_ENV.

if ($env !== 'testing') {
    echo "FATAL ERROR: APP_ENV must be testing. Received: {$env}\n";
    exit(1);
}

if (! $database) {
    echo "FATAL ERROR: Database name is empty.\n";
    exit(1);
}

if ($connection === 'sqlite') {
    if ($database !== ':memory:') {
        echo "FATAL ERROR: SQLite must use :memory: database.\n";
        exit(1);
    }
} elseif ($connection === 'mysql') {
    if ($database !== 'kaizenflow_test') {
        echo "FATAL ERROR: MySQL must strictly use kaizenflow_test database. Received: {$database}\n";
        exit(1);
    }
} else {
    echo "FATAL ERROR: Unsupported database connection driver: {$connection}\n";
    exit(1);
}

// Proceed to load the normal Composer autoloader
require dirname(__DIR__).'/vendor/autoload.php';
