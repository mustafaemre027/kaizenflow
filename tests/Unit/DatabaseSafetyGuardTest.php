<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use App\Testing\DatabaseSafetyGuard;

class DatabaseSafetyGuardTest extends TestCase
{
    public function test_sqlite_memory_is_allowed(): void
    {
        $this->expectNotToPerformAssertions();
        DatabaseSafetyGuard::check('testing', 'sqlite', ':memory:', null, null);
    }

    public function test_mysql_kaizenflow_test_is_allowed(): void
    {
        $this->expectNotToPerformAssertions();
        DatabaseSafetyGuard::check('testing', 'mysql', 'kaizenflow_test', '127.0.0.1', '3306');
    }

    public function test_local_env_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('APP_ENV must be testing');
        DatabaseSafetyGuard::check('local', 'mysql', 'kaizenflow', '127.0.0.1', '3306');
    }

    public function test_testing_env_with_mysql_and_wrong_database_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MySQL must strictly use kaizenflow_test database');
        DatabaseSafetyGuard::check('testing', 'mysql', 'kaizenflow', '127.0.0.1', '3306');
    }

    public function test_empty_database_name_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database name is empty');
        DatabaseSafetyGuard::check('testing', 'mysql', '', '127.0.0.1', '3306');
    }

    public function test_unsupported_driver_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported database connection driver');
        DatabaseSafetyGuard::check('testing', 'pgsql', 'kaizenflow_test', '127.0.0.1', '5432');
    }

    public function test_sqlite_with_file_database_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SQLite must use :memory: database');
        DatabaseSafetyGuard::check('testing', 'sqlite', 'database.sqlite', null, null);
    }

    public function test_exception_shows_safe_fields_but_no_credentials(): void
    {
        try {
            DatabaseSafetyGuard::check('testing', 'mysql', 'kaizenflow', '127.0.0.1', '3306');
            $this->fail('Should have thrown RuntimeException');
        } catch (RuntimeException $e) {
            $msg = $e->getMessage();
            $this->assertStringContainsString('Env: testing', $msg);
            $this->assertStringContainsString('Driver: mysql', $msg);
            $this->assertStringContainsString('DB: kaizenflow', $msg);
            $this->assertStringContainsString('Host: 127.0.0.1', $msg);
            $this->assertStringContainsString('Port: 3306', $msg);
            
            $this->assertStringNotContainsString('password', strtolower($msg));
        }
    }
}
