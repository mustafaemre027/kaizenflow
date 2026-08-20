<?php

namespace Tests\Feature\Models;

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_cannot_be_updated()
    {
        $log = AuditLog::factory()->create();
        $this->assertFalse($log->update(['event' => 'hacked']));
    }

    public function test_audit_log_cannot_be_deleted()
    {
        $log = AuditLog::factory()->create();
        $this->assertFalse($log->delete());
    }
}
