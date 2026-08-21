<?php

namespace Tests\Unit\Testing;

use PHPUnit\Framework\TestCase;
use Tests\Support\MySqlTestLauncher;
use RuntimeException;

class MySqlTestLauncherTest extends TestCase
{
    private string $tempEnvPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempEnvPath = sys_get_temp_dir() . '/.env.test.' . uniqid();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempEnvPath)) {
            unlink($this->tempEnvPath);
        }
        parent::tearDown();
    }

    private function createEnv(string $content): void
    {
        file_put_contents($this->tempEnvPath, $content);
    }

    public function test_fails_if_env_file_missing()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing or unreadable .env file');
        
        $launcher = new MySqlTestLauncher('/path/that/does/not/exist', []);
        $launcher->validatePreflight();
    }

    public function test_fails_if_db_host_is_not_localhost()
    {
        $this->createEnv("DB_HOST=10.0.0.1\nDB_PORT=3306\nDB_USERNAME=user\nDB_PASSWORD=pass");
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DB_HOST must be 127.0.0.1');

        $launcher = new MySqlTestLauncher($this->tempEnvPath, []);
        $launcher->loadEnvironment();
        $launcher->validatePreflight();
    }

    public function test_fails_if_db_port_invalid()
    {
        $this->createEnv("DB_HOST=127.0.0.1\nDB_PORT=invalid\nDB_USERNAME=user\nDB_PASSWORD=pass");
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DB_PORT must be a valid port number');

        $launcher = new MySqlTestLauncher($this->tempEnvPath, []);
        $launcher->loadEnvironment();
        $launcher->validatePreflight();
    }

    public function test_fails_if_username_is_root()
    {
        $this->createEnv("DB_HOST=127.0.0.1\nDB_PORT=3306\nDB_USERNAME=root\nDB_PASSWORD=pass");
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DB_USERNAME cannot be root');

        $launcher = new MySqlTestLauncher($this->tempEnvPath, []);
        $launcher->loadEnvironment();
        $launcher->validatePreflight();
    }

    public function test_fails_if_password_missing()
    {
        $this->createEnv("DB_HOST=127.0.0.1\nDB_PORT=3306\nDB_USERNAME=kaizenflow_app\nDB_PASSWORD=");
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DB_PASSWORD is required and cannot be empty');

        $launcher = new MySqlTestLauncher($this->tempEnvPath, []);
        $launcher->loadEnvironment();
        $launcher->validatePreflight();
    }

    public function test_builds_correct_child_environment()
    {
        $this->createEnv("DB_HOST=127.0.0.1\nDB_PORT=3306\nDB_USERNAME=kaizenflow_app\nDB_PASSWORD=secret123\nDB_DATABASE=dangerous");
        $launcher = new MySqlTestLauncher($this->tempEnvPath, []);
        $launcher->loadEnvironment();
        
        $childEnv = $launcher->buildChildEnvironment();
        
        $this->assertEquals('testing', $childEnv['APP_ENV']);
        $this->assertEquals('mysql', $childEnv['DB_CONNECTION']);
        $this->assertEquals('kaizenflow_test', $childEnv['DB_DATABASE']);
        $this->assertEquals('secret123', $childEnv['DB_PASSWORD']);
        $this->assertEquals('kaizenflow_app', $childEnv['DB_USERNAME']);
        // Assert it passes standard safe vars but not dangerous ones
        $this->assertArrayNotHasKey('DB_DATABASE=dangerous', $childEnv);
    }

    public function test_parent_environment_is_isolated()
    {
        $this->createEnv("DB_HOST=127.0.0.1\nDB_PORT=3306\nDB_USERNAME=kaizenflow_app\nDB_PASSWORD=secret123\nDUMMY_TEST_KEY=unique123");
        $launcher = new MySqlTestLauncher($this->tempEnvPath, []);
        $launcher->loadEnvironment();
        $launcher->buildChildEnvironment();
        
        // Assert parent env is not mutated by Dotenv::parse
        $this->assertFalse(getenv('DUMMY_TEST_KEY'));
        $this->assertArrayNotHasKey('DUMMY_TEST_KEY', $_ENV);
        $this->assertArrayNotHasKey('DUMMY_TEST_KEY', $_SERVER);
    }

    public function test_command_is_array_and_safe()
    {
        $this->createEnv("DB_HOST=127.0.0.1\nDB_PORT=3306\nDB_USERNAME=kaizenflow_app\nDB_PASSWORD=secret123");
        $launcher = new MySqlTestLauncher($this->tempEnvPath, ['--filter', 'SomeTest']);
        
        $command = $launcher->buildCommandArray();
        
        $this->assertIsArray($command);
        $this->assertMatchesRegularExpression('/php(\.exe)?$/i', $command[0]); // PHP_BINARY
        $this->assertStringEndsWith('phpunit', strtolower($command[1])); // PHPUnit
        $this->assertEquals('--configuration', $command[2]);
        $this->assertStringEndsWith('phpunit.mysql.xml', $command[3]);
        $this->assertEquals('--filter', $command[4]);
        $this->assertEquals('SomeTest', $command[5]);
        
        // Ensure password is not in command
        $this->assertNotContains('secret123', $command);
    }

    public function test_blocks_dangerous_cli_overrides()
    {
        $this->createEnv("DB_HOST=127.0.0.1\nDB_PORT=3306\nDB_USERNAME=kaizenflow_app\nDB_PASSWORD=secret123");
        $launcher = new MySqlTestLauncher($this->tempEnvPath, ['-c', 'phpunit.xml']);
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CLI argument -c is not allowed');
        $launcher->buildCommandArray();
    }

    public function test_blocks_dangerous_cli_bootstrap()
    {
        $this->createEnv("DB_HOST=127.0.0.1\nDB_PORT=3306\nDB_USERNAME=kaizenflow_app\nDB_PASSWORD=secret123");
        $launcher = new MySqlTestLauncher($this->tempEnvPath, ['--bootstrap=vendor/autoload.php']);
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CLI argument --bootstrap=vendor/autoload.php is not allowed');
        $launcher->buildCommandArray();
    }

    public function test_redaction_removes_secrets()
    {
        $this->createEnv("DB_HOST=127.0.0.1\nDB_PORT=3306\nDB_USERNAME=kaizenflow_app\nDB_PASSWORD=secret123");
        $launcher = new MySqlTestLauncher($this->tempEnvPath, []);
        $launcher->loadEnvironment();
        
        $output = "Exception: Connection failed for user secret123 and test";
        $redacted = $launcher->redactOutput($output);
        
        $this->assertEquals("Exception: Connection failed for user [REDACTED] and test", $redacted);
    }
}
