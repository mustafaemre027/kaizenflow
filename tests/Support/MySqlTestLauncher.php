<?php

namespace Tests\Support;

use Dotenv\Dotenv;
use RuntimeException;

class MySqlTestLauncher
{
    private string $envPath;

    private array $cliArgs;

    private array $env = [];

    public function __construct(string $envPath, array $cliArgs)
    {
        $this->envPath = $envPath;
        $this->cliArgs = $cliArgs;
    }

    public function loadEnvironment(): void
    {
        if (! file_exists($this->envPath) || ! is_readable($this->envPath)) {
            return;
        }

        $content = file_get_contents($this->envPath);
        $this->env = Dotenv::parse($content);
    }

    public function validatePreflight(): void
    {
        if (empty($this->env)) {
            throw new RuntimeException('Missing or unreadable .env file');
        }

        if (empty($this->env['DB_HOST']) || $this->env['DB_HOST'] !== '127.0.0.1') {
            throw new RuntimeException('DB_HOST must be 127.0.0.1');
        }

        if (empty($this->env['DB_PORT']) || ! is_numeric($this->env['DB_PORT']) || $this->env['DB_PORT'] < 1 || $this->env['DB_PORT'] > 65535) {
            throw new RuntimeException('DB_PORT must be a valid port number');
        }

        if (empty($this->env['DB_USERNAME'])) {
            throw new RuntimeException('DB_USERNAME is required and cannot be empty');
        }

        if ($this->env['DB_USERNAME'] !== 'kaizenflow_app') {
            throw new RuntimeException('DB_USERNAME must be exactly kaizenflow_app');
        }

        if (empty($this->env['DB_PASSWORD'])) {
            throw new RuntimeException('DB_PASSWORD is required and cannot be empty');
        }
    }

    public function buildChildEnvironment(): array
    {
        // Allowed safe environment variables to carry over (essential for PHP/Windows)
        $allowList = [
            'PATH', 'SystemRoot', 'WINDIR', 'COMSPEC', 'PATHEXT',
            'TEMP', 'TMP', 'USERPROFILE', 'APPDATA', 'LOCALAPPDATA',
        ];

        $childEnv = [];

        foreach ($_ENV as $key => $value) {
            if (in_array($key, $allowList, true)) {
                $childEnv[$key] = $value;
            }
        }

        foreach ($_SERVER as $key => $value) {
            if (in_array($key, $allowList, true)) {
                $childEnv[$key] = $value;
            }
        }

        // Add preflighted credentials
        $childEnv['DB_HOST'] = $this->env['DB_HOST'];
        $childEnv['DB_PORT'] = $this->env['DB_PORT'];
        $childEnv['DB_USERNAME'] = $this->env['DB_USERNAME'];
        $childEnv['DB_PASSWORD'] = $this->env['DB_PASSWORD'];

        // Force testing isolation tuple
        $childEnv['APP_ENV'] = 'testing';
        $childEnv['DB_CONNECTION'] = 'mysql';
        $childEnv['DB_DATABASE'] = 'kaizenflow_test';

        return $childEnv;
    }

    public function buildCommandArray(): array
    {
        $disallowedPrefixes = [
            '-c', '--configuration', '--no-configuration',
            '--bootstrap', '--prepend',
        ];

        $safeArgs = [];

        foreach ($this->cliArgs as $arg) {
            foreach ($disallowedPrefixes as $prefix) {
                if ($arg === $prefix || str_starts_with($arg, $prefix.'=')) {
                    throw new RuntimeException("CLI argument {$arg} is not allowed");
                }
            }
            $safeArgs[] = $arg;
        }

        $command = [
            PHP_BINARY,
            dirname(__DIR__, 2).'/vendor/phpunit/phpunit/phpunit',
            '--configuration',
            dirname(__DIR__, 2).'/phpunit.mysql.xml',
        ];

        return array_merge($command, $safeArgs);
    }

    public function redactOutput(string $output): string
    {
        if (empty($this->env['DB_PASSWORD'])) {
            return $output;
        }

        return str_replace($this->env['DB_PASSWORD'], '[REDACTED]', $output);
    }

    public function run(): int
    {
        $this->loadEnvironment();
        $this->validatePreflight();
        $childEnv = $this->buildChildEnvironment();
        $commandArray = $this->buildCommandArray();

        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $process = proc_open($commandArray, $descriptors, $pipes, null, $childEnv);

        if (! is_resource($process)) {
            throw new RuntimeException('Failed to spawn child process via proc_open');
        }

        fclose($pipes[0]); // We do not need to write to stdin

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($stdout !== false && $stdout !== '') {
            echo $this->redactOutput($stdout);
        }

        if ($stderr !== false && $stderr !== '') {
            fwrite(STDERR, $this->redactOutput($stderr));
        }

        return $exitCode;
    }
}
