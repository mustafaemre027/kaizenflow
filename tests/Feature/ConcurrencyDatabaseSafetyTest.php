<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConcurrencyDatabaseSafetyTest extends TestCase
{
    public function test_child_process_must_be_kaizenflow_test()
    {
        $scriptPath = base_path('tests/mysql-launcher.php');
        $this->assertFileExists($scriptPath);

        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];

        $env = getenv();
        $env['DB_DATABASE'] = 'kaizenflow';
        $env['APP_ENV'] = 'local';
        $originalEnv = getenv();

        $process = proc_open(PHP_BINARY.' '.escapeshellarg($scriptPath).' --db-check', $descriptors, $pipes, null, $env);
        $output = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_get_status($process);
        proc_close($process);

        $this->assertEquals(0, $status['exitcode'], 'Launcher failed: '.$stderr);
        $this->assertStringContainsString('DATABASE:kaizenflow_test', $output, 'Child escaped to another database!');
        $this->assertEquals($originalEnv, getenv(), 'Parent env must not change!');
        $this->assertStringNotContainsString(env('DB_PASSWORD', 'secret'), $output);
    }

    public function test_direct_worker_invocation_rejected()
    {
        $workerPath = base_path('tests/Support/MySqlTestWorker.php');
        if (! file_exists($workerPath)) {
            $this->markTestSkipped('Worker not created yet.');
        }

        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open(PHP_BINARY.' '.escapeshellarg($workerPath), $descriptors, $pipes, null, []);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_get_status($process);
        proc_close($process);

        $this->assertNotEquals(0, $status['exitcode']);
    }

    public function test_safe_canary_execution()
    {
        $scriptPath = base_path('tests/mysql-launcher.php');
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];

        $env = getenv();
        $process = proc_open(PHP_BINARY.' '.escapeshellarg($scriptPath).' --canary', $descriptors, $pipes, null, $env);
        $output = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_get_status($process);
        proc_close($process);

        $this->assertEquals(0, $status['exitcode'], 'Canary failed: '.$stderr.' / '.$output);
        $this->assertStringContainsString('CANARY_SUCCESS', $output);
    }
}
