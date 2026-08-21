<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class EarlyBootstrapSafetyTest extends TestCase
{
    private function runBootstrap(array $env): array
    {
        // Run tests/bootstrap.php via PHP CLI to simulate early execution
        // before PHPUnit or Laravel is involved.
        $result = Process::env($env)->run('php tests/bootstrap.php');
        
        return [
            'exitCode' => $result->exitCode(),
            'output' => $result->output() . $result->errorOutput(),
        ];
    }

    public function test_early_bootstrap_accepts_safe_sqlite_tuple()
    {
        $res = $this->runBootstrap([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
        ]);

        $this->assertEquals(0, $res['exitCode'], 'Safe SQLite tuple should be accepted.');
    }

    public function test_early_bootstrap_accepts_safe_mysql_tuple()
    {
        $res = $this->runBootstrap([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => 'kaizenflow_test',
        ]);

        $this->assertEquals(0, $res['exitCode'], 'Safe MySQL tuple should be accepted.');
    }

    public function test_early_bootstrap_rejects_dangerous_mysql_kaizenflow_tuple()
    {
        $res = $this->runBootstrap([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => 'kaizenflow',
        ]);

        $this->assertNotEquals(0, $res['exitCode'], 'Dangerous tuple must produce non-zero exit code.');
        $this->assertStringContainsString('FATAL ERROR', $res['output']);
        $this->assertStringContainsString('kaizenflow_test', $res['output']);
        $this->assertStringNotContainsString('password', strtolower($res['output']));
        $this->assertStringNotContainsString('secret', strtolower($res['output']));
    }

    public function test_early_bootstrap_rejects_missing_env_testing()
    {
        $res = $this->runBootstrap([
            'APP_ENV' => 'local',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
        ]);

        $this->assertNotEquals(0, $res['exitCode']);
        $this->assertStringContainsString('APP_ENV must be testing', $res['output']);
    }

    public function test_early_bootstrap_failure_prevents_laravel_application_creation()
    {
        // If the bootstrap fails, Laravel's artisan / app bootstrap is literally unreachable
        // because the process exits before 'require vendor/autoload.php' is completed or tests begin.
        $res = $this->runBootstrap([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => 'kaizenflow',
        ]);
        
        $this->assertNotEquals(0, $res['exitCode']);
        // If it exited early with our fatal error, it did not boot Laravel.
        // There is no stack trace or Laravel exception output.
        $this->assertStringNotContainsString('Illuminate\\', $res['output']);
        $this->assertStringNotContainsString('Stack trace:', $res['output']);
    }

    public function test_parent_boot_artisan_test_effective_config_is_sqlite_memory()
    {
        // Execute the real artisan test command, but target a canary script that outputs config
        // Since we are simulating missing .env.testing, if it falls back it would be dangerous.
        // We expect it to be forced to sqlite :memory: by the safe phpunit.xml
        
        // Create a temporary test file that dumps config
        $testCode = <<<PHP
<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Support\Facades\Config;
class TempConfigDumpTest extends TestCase {
    public function test_dump() {
        echo "CANARY_DB:" . Config::get('database.connections.' . Config::get('database.default') . '.database');
        echo "CANARY_CONN:" . Config::get('database.default');
        \$this->assertTrue(true);
    }
}
PHP;
        file_put_contents(base_path('tests/Feature/TempConfigDumpTest.php'), $testCode);
        
        // Simulating the environment that would be present when running `php artisan test`
        // We clear existing testing env vars so it relies on defaults and phpunit.xml
        $result = Process::env([
            'APP_ENV' => false,
            'DB_CONNECTION' => false,
            'DB_DATABASE' => false
        ])->run('php artisan test tests/Feature/TempConfigDumpTest.php');
        
        @unlink(base_path('tests/Feature/TempConfigDumpTest.php'));
        
        // For the RED phase, this might actually show kaizenflow if force=true is not enough
        // but we already added force=true in previous block! So it might pass.
        // Wait, the RED phase for parent boot requires us to intercept it before application creation!
        
        // We just assert what the requirement states:
        $this->assertStringContainsString('CANARY_DB::memory:', $result->output());
        $this->assertStringContainsString('CANARY_CONN:sqlite', $result->output());
    }

    public function test_mysql_launcher_forces_kaizenflow_test()
    {
        // Run composer test:mysql targeting the same canary
        $testCode = <<<PHP
<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
class TempMysqlDumpTest extends TestCase {
    public function test_dump() {
        echo "CANARY_CONN:" . Config::get('database.default');
        echo "CANARY_DB:" . DB::connection()->getDatabaseName();
        \$this->assertTrue(true);
    }
}
PHP;
        file_put_contents(base_path('tests/Feature/TempMysqlDumpTest.php'), $testCode);
        
        $result = Process::run('composer test:mysql -- --filter TempMysqlDumpTest');
        @unlink(base_path('tests/Feature/TempMysqlDumpTest.php'));
        
        // We assert it uses mysql and exactly kaizenflow_test
        $this->assertStringContainsString('CANARY_CONN:mysql', $result->output());
        $this->assertStringContainsString('CANARY_DB:kaizenflow_test', $result->output());
    }
}
