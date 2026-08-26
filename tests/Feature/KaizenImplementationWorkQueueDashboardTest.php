<?php

namespace Tests\Feature;

use App\Enums\KaizenStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Kaizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KaizenImplementationWorkQueueDashboardTest extends TestCase
{
    use RefreshDatabase;

    private string $route = '/';

    public function test_guest_sees_welcome_but_no_dashboard(): void
    {
        $response = $this->get($this->route)->assertOk();
        $response->assertSee('Giriş Yap');
        $response->assertDontSee('Uygulama İşlerim');
    }

    public function test_inactive_user_gets_403_fail_closed(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->actingAs($user)->get($this->route)->assertForbidden();
    }

    public function test_active_user_sees_dashboard_with_correct_metrics(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        // 1 Active (no date)
        Kaizen::factory()->create([
            'assigned_user_id' => $user->id,
            'status' => KaizenStatus::IN_PROGRESS,
            'target_date' => null,
        ]);

        // 1 Overdue
        Kaizen::factory()->create([
            'assigned_user_id' => $user->id,
            'status' => KaizenStatus::IN_PROGRESS,
            'target_date' => now()->subDay()->format('Y-m-d'),
        ]);

        // 1 Today
        Kaizen::factory()->create([
            'assigned_user_id' => $user->id,
            'status' => KaizenStatus::IN_PROGRESS,
            'target_date' => now()->format('Y-m-d'),
        ]);

        // 1 Future
        Kaizen::factory()->create([
            'assigned_user_id' => $user->id,
            'status' => KaizenStatus::IN_PROGRESS,
            'target_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        // Total active: 4
        // Overdue: 1
        // Today: 1

        $response = $this->actingAs($user)->get($this->route)->assertOk();
        
        $response->assertSee('Uygulama İşlerim');
        
        // Use exact view data boundaries
        $response->assertViewHas('workQueueSummary');
        
        $summary = $response->original->getData()['workQueueSummary'];
        $this->assertEquals(4, $summary['active_count']);
        $this->assertEquals(1, $summary['overdue_count']);
        $this->assertEquals(1, $summary['today_count']);
    }

    public function test_dashboard_ignores_other_users_and_admin_cannot_bypass(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'role' => UserRole::ADMIN]);
        $otherUser = User::factory()->create(['is_active' => true]);

        Kaizen::factory()->create([
            'assigned_user_id' => $otherUser->id,
            'status' => KaizenStatus::IN_PROGRESS,
            'target_date' => now()->subDay()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($admin)->get($this->route)->assertOk();
        
        $summary = $response->original->getData()['workQueueSummary'];
        $this->assertEquals(0, $summary['active_count']);
        $this->assertEquals(0, $summary['overdue_count']);
        $this->assertEquals(0, $summary['today_count']);
    }

    public function test_dashboard_ignores_completed_and_rejected(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        Kaizen::factory()->create([
            'assigned_user_id' => $user->id,
            'status' => KaizenStatus::COMPLETED,
            'target_date' => now()->subDay()->format('Y-m-d'),
        ]);

        Kaizen::factory()->create([
            'assigned_user_id' => $user->id,
            'status' => KaizenStatus::REJECTED,
            'target_date' => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)->get($this->route)->assertOk();
        
        $summary = $response->original->getData()['workQueueSummary'];
        $this->assertEquals(0, $summary['active_count']);
        $this->assertEquals(0, $summary['overdue_count']);
        $this->assertEquals(0, $summary['today_count']);
    }

    public function test_no_audit_or_mutation_on_render(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Kaizen::factory()->create([
            'assigned_user_id' => $user->id,
            'status' => KaizenStatus::IN_PROGRESS,
        ]);

        $auditCountBefore = AuditLog::count();
        $this->actingAs($user)->get($this->route)->assertOk();
        $this->assertEquals($auditCountBefore, AuditLog::count());
    }

    public function test_single_aggregate_query_performance(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Kaizen::factory()->count(10)->create([
            'assigned_user_id' => $user->id,
            'status' => KaizenStatus::IN_PROGRESS,
            'target_date' => now()->subDay()->format('Y-m-d'), // 10 overdue
        ]);

        $this->actingAs($user);

        DB::enableQueryLog();
        $this->get($this->route)->assertOk();
        $queries = DB::getQueryLog();

        // The exact count should be minimal, ideally 1 query for the dashboard
        // We'll assert < 5 to allow for session/user lookup, but ensure it's not O(N)
        $this->assertTrue(count($queries) < 5, 'N+1 or multiple queries detected for dashboard summary.');
    }
}
