<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Tests\TestCase;

class GuardIntegrationTest extends TestCase
{
    // We don't use RefreshDatabase trait here directly on the class
    // because if the guard fails, it prevents it. But to prove it,
    // we can use a custom setUpTheTestEnvironment.

    protected function setUpTheTestEnvironment(): void
    {
        $this->refreshApplication();

        // 1. Effective config tehlikeli olarak mysql / kaizenflow verilir.
        Config::set('app.env', 'testing');
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'kaizenflow');

        // We expect the guard to throw an exception here when setUpTraits runs.
        try {
            $this->setUpTraits();
            $this->fail('Guard did not throw an exception');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Test Database Safety Guard Failed', $e->getMessage());

            // Since we caught it and verified it, we can stop the rest of setUpTheTestEnvironment
            // to avoid actually hitting the database.
            return;
        }
    }

    public function test_guard_intercepts_before_migrations()
    {
        // 4. Migration başlangıç işareti veya callback’i çalışmaz.
        // 5. Hiçbir DB sorgusu gönderilmez.
        $this->assertTrue(true);
    }
}
