<?php

namespace Tests\Support;

use RuntimeException;
use Tests\Support\MySqlTestLauncher;

class RaceHarness
{
    private string $barrierDir;
    private array $workers = [];
    private bool $cleanupCalled = false;

    public function __construct()
    {
        $this->barrierDir = sys_get_temp_dir() . '/kaizen_race_' . uniqid();
        mkdir($this->barrierDir);
    }

    public function getBarrierDir(): string
    {
        return $this->barrierDir;
    }

    public function spawnWorker(string $type, string $workerId, array $payload): array
    {
        $payloadJson = json_encode($payload);
        $args = [
            '--race-worker',
            "--race-type={$type}",
            "--barrier-dir={$this->barrierDir}",
            "--worker-id={$workerId}",
            "--payload={$payloadJson}"
        ];

        $launcher = new MySqlTestLauncher(base_path('.env'), $args);
        $launcher->loadEnvironment();
        $launcher->validatePreflight();

        $childEnv = $launcher->buildChildEnvironment();
        $commandArray = $launcher->buildCommandArray();

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($commandArray, $descriptors, $pipes, null, $childEnv);
        if (!is_resource($process)) {
            throw new RuntimeException('Could not start child process');
        }

        fclose($pipes[0]);

        $worker = [
            'process' => $process,
            'pipes' => $pipes,
            'id' => $workerId
        ];
        
        $this->workers[] = $worker;

        return $worker;
    }

    public function waitForReady(array $workers = null): void
    {
        $workersToWait = $workers ?? $this->workers;
        $waited = 0;
        foreach ($workersToWait as $worker) {
            $readyFile = $this->barrierDir . '/' . $worker['id'] . '.ready';
            while (!file_exists($readyFile)) {
                if ($waited > 50) { // 5 seconds
                    throw new RuntimeException('Timeout waiting for worker ready');
                }
                usleep(100000);
                $waited++;
            }
        }
    }

    public function releaseWorkers(): void
    {
        file_put_contents($this->barrierDir . '/release.go', 'go');
    }

    public function collectResults(array $workers = null): array
    {
        $workersToCollect = $workers ?? $this->workers;
        $results = [];
        foreach ($workersToCollect as $worker) {
            $stdout = stream_get_contents($worker['pipes'][1]);
            fclose($worker['pipes'][1]);

            $stderr = stream_get_contents($worker['pipes'][2]);
            fclose($worker['pipes'][2]);

            $exitCode = proc_close($worker['process']);
            $results[] = [
                'id' => $worker['id'],
                'exitcode' => $exitCode,
                'stdout' => $stdout,
                'stderr' => $stderr
            ];
        }
        return $results;
    }

    public function cleanup(): void
    {
        if ($this->cleanupCalled) {
            throw new RuntimeException('Cleanup already called');
        }
        
        if (is_dir($this->barrierDir)) {
            $files = glob($this->barrierDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->barrierDir);
        }
        $this->cleanupCalled = true;
    }
}
