<?php

namespace Tests\Support;

use RuntimeException;

class RaceHarness
{
    private string $barrierDir;

    private array $workers = [];

    private bool $cleanedUp = false;

    public function __construct()
    {
        $this->barrierDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'kaizen_race_'.bin2hex(random_bytes(16));
        if (! mkdir($this->barrierDir)) {
            throw new RuntimeException("Could not create barrier directory: {$this->barrierDir}");
        }
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
            "--payload={$payloadJson}",
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
        if (! is_resource($process)) {
            throw new RuntimeException('Could not start child process');
        }

        fclose($pipes[0]);

        $worker = [
            'process' => $process,
            'pipes' => $pipes,
            'id' => $workerId,
        ];

        $this->workers[] = $worker;

        return $worker;
    }

    public function waitForReady(?array $workers = null): void
    {
        $workersToWait = $workers ?? $this->workers;
        $waited = 0;
        foreach ($workersToWait as $worker) {
            $readyFile = $this->barrierDir.DIRECTORY_SEPARATOR.$worker['id'].'.ready';
            while (! file_exists($readyFile)) {
                if ($waited > 50) { // 5 seconds
                    throw new RuntimeException('Timeout waiting for worker ready');
                }
                usleep(100000); // 100ms
                $waited++;
            }
        }
    }

    public function releaseWorkers(): void
    {
        file_put_contents($this->barrierDir.DIRECTORY_SEPARATOR.'release.go', 'go');
    }

    public function collectResults(?array $workers = null): array
    {
        $workersToCollect = $workers ?? $this->workers;
        $results = [];
        foreach ($workersToCollect as $worker) {
            $stdout = '';
            $stderr = '';

            if (is_resource($worker['pipes'][1])) {
                $stdout = stream_get_contents($worker['pipes'][1]);
                fclose($worker['pipes'][1]);
            }

            if (is_resource($worker['pipes'][2])) {
                $stderr = stream_get_contents($worker['pipes'][2]);
                fclose($worker['pipes'][2]);
            }

            $exitCode = -1;
            if (is_resource($worker['process'])) {
                $status = proc_get_status($worker['process']);
                if ($status['running']) {
                    proc_terminate($worker['process']);
                }
                $exitCode = proc_close($worker['process']);
            }

            $results[] = [
                'id' => $worker['id'],
                'exitcode' => $exitCode,
                'stdout' => $stdout,
                'stderr' => $stderr,
            ];
        }

        return $results;
    }

    public function cleanup(): void
    {
        if ($this->cleanedUp) {
            return;
        }

        // Ensure all workers are terminated and pipes closed
        foreach ($this->workers as $worker) {
            if (is_resource($worker['pipes'][1])) {
                fclose($worker['pipes'][1]);
            }
            if (is_resource($worker['pipes'][2])) {
                fclose($worker['pipes'][2]);
            }
            if (is_resource($worker['process'])) {
                $status = proc_get_status($worker['process']);
                if ($status['running']) {
                    proc_terminate($worker['process']);
                }
                proc_close($worker['process']);
            }
        }

        // Exact owned barrier directory validation
        if (is_dir($this->barrierDir) && str_contains($this->barrierDir, 'kaizen_race_')) {
            $files = glob($this->barrierDir.DIRECTORY_SEPARATOR.'*');
            if ($files !== false) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
            rmdir($this->barrierDir);
        }

        $this->cleanedUp = true;
    }
}
