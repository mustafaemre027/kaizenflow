<?php

namespace Tests\Feature;

use Exception;
use RuntimeException;
use Tests\Support\RaceHarness;
use Tests\TestCase;

class RaceHarnessCleanupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (env('DB_CONNECTION') !== 'mysql') {
            $this->markTestSkipped('Concurrency tests require MySQL.');
        }
    }

    public function test_normal_completion_cleans_up_barrier_dir()
    {
        $harness = new RaceHarness;
        $dir = $harness->getBarrierDir();
        $this->assertDirectoryExists($dir);

        $harness->cleanup();

        $this->assertDirectoryDoesNotExist($dir);
    }

    public function test_cleanup_is_idempotent()
    {
        $harness = new RaceHarness;
        $harness->cleanup();

        $exceptionThrown = false;
        try {
            $harness->cleanup();
        } catch (\Throwable $e) {
            $exceptionThrown = true;
        }

        $this->assertFalse($exceptionThrown, 'Cleanup should be idempotent and not throw an exception when called twice.');
    }

    public function test_parent_exception_cleans_up_barrier_dir()
    {
        $harness = null;
        $dir = null;
        try {
            $harness = new RaceHarness;
            $dir = $harness->getBarrierDir();
            throw new Exception('Parent failed');
        } catch (Exception $e) {
            if ($harness) {
                $harness->cleanup();
            }
        }

        $this->assertDirectoryDoesNotExist($dir, 'Parent exception should not leave barrier dir behind');
    }

    public function test_child_timeout_cleans_up_barrier_dir()
    {
        $harness = new RaceHarness;
        $dir = $harness->getBarrierDir();

        try {
            $harness->waitForReady([['id' => 'w1']]);
        } catch (RuntimeException $e) {
            $harness->cleanup();
        }

        $this->assertDirectoryDoesNotExist($dir, 'Timeout should not leave barrier dir behind');
    }

    public function test_cleanup_only_deletes_exact_dir()
    {
        $harness = new RaceHarness;
        $dir = $harness->getBarrierDir();

        $unrelated = sys_get_temp_dir().'/kaizen_race_unrelated_'.uniqid();
        @mkdir($unrelated);

        $harness->cleanup();

        $this->assertDirectoryDoesNotExist($dir);
        $this->assertDirectoryExists($unrelated);

        rmdir($unrelated);
    }

    public function test_two_harnesses_have_different_dirs()
    {
        $h1 = new RaceHarness;
        $h2 = new RaceHarness;

        $this->assertNotEquals($h1->getBarrierDir(), $h2->getBarrierDir());

        $h1->cleanup();
        $h2->cleanup();
    }
}
