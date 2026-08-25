<?php

namespace Tests\Unit\Testing;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\MySqlTestLauncher;

class MySqlTestLauncherResidualBypassTest extends TestCase
{
    private string $tempEnvPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempEnvPath = sys_get_temp_dir().'/.env.test.'.uniqid();
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

    public static function nonAllowlistedUsernamesProvider(): array
    {
        return [
            ['admin'],
            ['administrator'],
            ['mysql'],
            ['test_admin'],
            ['kaizenflow_app2'],
            ['" kaizenflow_app"'],
            ['"kaizenflow_app "'],
            ['KAIZENFLOW_APP'],
        ];
    }

    #[DataProvider('nonAllowlistedUsernamesProvider')]
    public function test_fails_if_username_is_not_strictly_allowlisted(string $username)
    {
        $this->createEnv("DB_HOST=127.0.0.1\nDB_PORT=3306\nDB_USERNAME={$username}\nDB_PASSWORD=pass");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DB_USERNAME must be exactly kaizenflow_app');

        $launcher = new MySqlTestLauncher($this->tempEnvPath, []);
        $launcher->loadEnvironment();
        $launcher->validatePreflight();
    }

    public static function configurationBypassProvider(): array
    {
        return [
            [['-c']],
            [['-c=evil.xml']],
            [['-c', 'evil.xml']],
            [['--configuration']],
            [['--configuration', 'evil.xml']],
            [['--configuration=evil.xml']],
            [['--no-configuration']],
            [['--bootstrap']],
            [['--bootstrap', 'evil.php']],
            [['--bootstrap=evil.php']],
            [['--prepend']],
            [['--prepend', 'evil.php']],
            [['--prepend=evil.php']],
        ];
    }

    #[DataProvider('configurationBypassProvider')]
    public function test_blocks_all_configuration_bypass_variants(array $args)
    {
        $this->createEnv("DB_HOST=127.0.0.1\nDB_PORT=3306\nDB_USERNAME=kaizenflow_app\nDB_PASSWORD=pass");
        $launcher = new MySqlTestLauncher($this->tempEnvPath, $args);

        $this->expectException(RuntimeException::class);
        $launcher->buildCommandArray();
    }

    public static function safeForwardingProvider(): array
    {
        return [
            [['--filter', 'SomeTest']],
            [['--filter=SomeTest']],
            [['--testsuite', 'Feature']],
            [['--testsuite=Feature']],
            [['--testdox']],
            [['--stop-on-failure']],
            [['tests/Feature/SomeTest.php']],
        ];
    }

    #[DataProvider('safeForwardingProvider')]
    public function test_forwards_safe_arguments(array $args)
    {
        $this->createEnv("DB_HOST=127.0.0.1\nDB_PORT=3306\nDB_USERNAME=kaizenflow_app\nDB_PASSWORD=pass");
        $launcher = new MySqlTestLauncher($this->tempEnvPath, $args);

        $command = $launcher->buildCommandArray();

        foreach ($args as $arg) {
            $this->assertContains($arg, $command);
        }
    }

    public static function dotenvEdgeCaseProvider(): array
    {
        return [
            'Boşluk içeren parola' => ['DB_PASSWORD="secret word"', 'secret word'],
            '# içeren parola' => ['DB_PASSWORD="secret#123"', 'secret#123'],
            '= içeren parola' => ['DB_PASSWORD="secret=123"', 'secret=123'],
            'Tek tırnaklı değer' => ["DB_PASSWORD='secret'", 'secret'],
            'Çift tırnaklı değer' => ['DB_PASSWORD="secret"', 'secret'],
            'CRLF satır sonu' => ["DB_PASSWORD=secret\r\nOTHER_KEY=127.0.0.1", 'secret'],
            'LF satır sonu' => ["DB_PASSWORD=secret\nOTHER_KEY=127.0.0.1", 'secret'],
            'Unicode/Türkçe karakter' => ['DB_PASSWORD=şifre123', 'şifre123'],
            'Escaped karakter' => ['DB_PASSWORD="secret\\"123"', 'secret"123'],
            'Son satırda newline olmaması' => ['DB_PASSWORD=secret', 'secret'],
            'Yorum satırı' => ["# Bu bir yorumdur\nDB_PASSWORD=secret", 'secret'],
            'Boş satır' => ["\n\nDB_PASSWORD=secret\n\n", 'secret'],
            'Başında/sonunda boşluk' => ['DB_PASSWORD="  secret  "', '  secret  '],
        ];
    }

    #[DataProvider('dotenvEdgeCaseProvider')]
    public function test_parser_handles_edge_cases(string $envContent, string $expectedPassword)
    {
        $base = "DB_HOST=127.0.0.1\nDB_PORT=3306\nDB_USERNAME=kaizenflow_app\n";
        $this->createEnv($base.$envContent);

        $launcher = new MySqlTestLauncher($this->tempEnvPath, []);
        $launcher->loadEnvironment();

        $childEnv = $launcher->buildChildEnvironment();
        $this->assertEquals($expectedPassword, $childEnv['DB_PASSWORD']);
    }

    public function test_rejects_duplicate_database_keys()
    {
        $base = "DB_HOST=127.0.0.1\nDB_PORT=3306\nDB_USERNAME=kaizenflow_app\nDB_PASSWORD=first\nDB_PASSWORD=second\n";
        $this->createEnv($base);

        $launcher = new MySqlTestLauncher($this->tempEnvPath, []);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Duplicate definition found for DB_PASSWORD');

        $launcher->loadEnvironment();
    }
}
