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
        $harness = new RaceHarness();
        $dir = $harness->getBarrierDir();
        $this->assertDirectoryExists($dir);
        
        $harness->cleanup();
        
        $this->assertDirectoryDoesNotExist($dir);
    }

    public function test_cleanup_called_twice_throws_exception_currently()
    {
        $harness = new RaceHarness();
        $harness->cleanup();
        
        // This is a current flaw that will be fixed (we want it to NOT throw exception)
        // But for the RED test, the instruction says: "Cleanup iki kez çağrıldığında exception oluşmaz."
        // We will assert that it DOES NOT throw an exception, so it will FAIL in RED.
        
        $exceptionThrown = false;
        try {
            $harness->cleanup();
        } catch (\Throwable $e) {
            $exceptionThrown = true;
        }
        
        $this->assertFalse($exceptionThrown, 'Cleanup should be idempotent and not throw an exception when called twice.');
    }

    public function test_parent_exception_leaves_barrier_dir_currently()
    {
        $harness = null;
        $dir = null;
        try {
            $harness = new RaceHarness();
            $dir = $harness->getBarrierDir();
            throw new Exception('Parent failed');
        } catch (Exception $e) {
            // Harness did not clean up in finally
        }
        
        // This will fail because the dir STILL exists, but we assert it shouldn't.
        $this->assertDirectoryDoesNotExist($dir, 'Parent exception should not leave barrier dir behind');
        if (is_dir($dir)) rmdir($dir);
    }

    public function test_child_timeout_leaves_barrier_dir_currently()
    {
        $harness = new RaceHarness();
        $dir = $harness->getBarrierDir();
        
        try {
            // Wait for workers that don't exist, triggering timeout
            $harness->waitForReady([['id' => 'w1']]);
        } catch (RuntimeException $e) {
            // timeout happened
        }
        
        // This will fail because cleanup wasn't called in finally.
        $this->assertDirectoryDoesNotExist($dir, 'Timeout should not leave barrier dir behind');
        if (is_dir($dir)) rmdir($dir);
    }

    public function test_cleanup_only_deletes_exact_dir()
    {
        $harness = new RaceHarness();
        $dir = $harness->getBarrierDir();
        
        // Create an unrelated kaizen_race directory
        $unrelated = sys_get_temp_dir() . '/kaizen_race_unrelated';
        @mkdir($unrelated);
        
        $harness->cleanup();
        
        $this->assertDirectoryDoesNotExist($dir);
        $this->assertDirectoryExists($unrelated);
        
        rmdir($unrelated);
    }

    public function test_two_harnesses_have_different_dirs()
    {
        $h1 = new RaceHarness();
        $h2 = new RaceHarness();
        
        $this->assertNotEquals($h1->getBarrierDir(), $h2->getBarrierDir());
        
        $h1->cleanup();
        $h2->cleanup();
    }
}
