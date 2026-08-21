<?php

namespace App\Testing;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DatabaseSafetyGuard
{
    public static function verify(): void
    {
        $env = Config::get('app.env');
        $connection = Config::get('database.default');
        $database = Config::get("database.connections.{$connection}.database");
        $host = Config::get("database.connections.{$connection}.host");
        $port = Config::get("database.connections.{$connection}.port");

        self::check($env, $connection, $database, $host, $port);

        // Additionally verify the actual connected database name if it is MySQL
        if ($connection === 'mysql') {
            $pdo = DB::connection($connection)->getPdo();
            $stmt = $pdo->query('SELECT DATABASE()');
            $actualDb = $stmt->fetchColumn();

            if ($actualDb !== 'kaizenflow_test') {
                self::abort($env, $connection, $actualDb, $host, $port, 'Active MySQL database is not kaizenflow_test.');
            }
        }
    }

    public static function check(?string $env, ?string $connection, ?string $database, ?string $host, ?string $port): void
    {
        if ($env !== 'testing') {
            self::abort($env, $connection, $database, $host, $port, 'APP_ENV must be testing.');
        }

        if (! $database) {
            self::abort($env, $connection, $database, $host, $port, 'Database name is empty.');
        }

        if ($connection === 'sqlite') {
            if ($database !== ':memory:') {
                self::abort($env, $connection, $database, $host, $port, 'SQLite must use :memory: database.');
            }
            return;
        }

        if ($connection === 'mysql') {
            if ($database !== 'kaizenflow_test') {
                self::abort($env, $connection, $database, $host, $port, 'MySQL must strictly use kaizenflow_test database.');
            }
            return;
        }

        self::abort($env, $connection, $database, $host, $port, 'Unsupported database connection driver.');
    }

    private static function abort(?string $env, ?string $connection, ?string $database, ?string $host, ?string $port, string $reason): void
    {
        $hostStr = $host ?: 'null';
        $portStr = $port ?: 'null';
        $dbStr = $database ?: 'null';
        $envStr = $env ?: 'null';
        $connStr = $connection ?: 'null';

        throw new RuntimeException(
            "Test Database Safety Guard Failed: {$reason} " .
            "[Env: {$envStr}, Driver: {$connStr}, DB: {$dbStr}, Host: {$hostStr}, Port: {$portStr}]. " .
            "Allowed paths: (sqlite + :memory:) OR (mysql + kaizenflow_test)."
        );
    }
}
