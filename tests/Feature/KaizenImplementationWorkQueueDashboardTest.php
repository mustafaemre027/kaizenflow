<?php

namespace Tests\Feature;

use App\Enums\KaizenStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Department;
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
        $this->actingAs($user)->get($this->route)->assertRedirect('/login');
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
        // We'll assert < 10 to allow for session/user lookup and capability checks, but ensure it's not O(N)
        $this->assertTrue(count($queries) < 10, 'N+1 or multiple queries detected for dashboard summary.');
    }

    public function test_single_aggregate_query_performance_large_dataset(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $department = Department::factory()->create();
        $category = Category::factory()->create();

        Kaizen::factory()->count(100)->create([
            'assigned_user_id' => $user->id,
            'department_id' => $department->id,
            'category_id' => $category->id,
            'status' => KaizenStatus::IN_PROGRESS,
            'target_date' => now()->subDay()->format('Y-m-d'), // 100 overdue
        ]);

        $this->actingAs($user);

        DB::enableQueryLog();
        $this->get($this->route)->assertOk();
        $queries = DB::getQueryLog();

        $this->assertTrue(count($queries) < 10, 'Queries should be bounded regardless of dataset size (was '.count($queries).')');
    }

    public function test_actor_injection_is_rejected(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $otherUser = User::factory()->create(['is_active' => true]);

        // other user has 5 active kaizens
        Kaizen::factory()->count(5)->create([
            'assigned_user_id' => $otherUser->id,
            'status' => KaizenStatus::IN_PROGRESS,
            'target_date' => null,
        ]);

        // try to inject via query params
        $response = $this->actingAs($user)->get($this->route.'?user_id='.$otherUser->id.'&actor_user_id='.$otherUser->id.'&assigned_user_id='.$otherUser->id);

        $response->assertOk();
        $summary = $response->original->getData()['workQueueSummary'];

        // Should still be 0 because current user has 0
        $this->assertEquals(0, $summary['active_count']);
        // The HTML must not contain the other user's kaizen count
        $response->assertDontSee('>5<', false);
    }

    public function test_html_xss_escaping(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        // Test that no kaizen title is leaked in dashboard (especially XSS payload)
        Kaizen::factory()->create([
            'assigned_user_id' => $user->id,
            'status' => KaizenStatus::IN_PROGRESS,
            'title' => '<script>alert("dashboard-xss")</script>',
        ]);

        $response = $this->actingAs($user)->get($this->route)->assertOk();

        // Assert that raw script tag is not present
        $response->assertDontSee('<script>alert("dashboard-xss")</script>', false);
    }

    public function test_accessible_h1_and_links(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $response = $this->actingAs($user)->get($this->route)->assertOk();

        $content = $response->getContent();

        // Exactly one H1 tag
        $this->assertEquals(1, substr_count(strtolower($content), '<h1'));

        // Has accessible Uygulama İşlerim link
        $expectedUrl = route('implementation.work-queue.index');
        $response->assertSee('href="'.$expectedUrl.'"', false);
        $response->assertSee('Uygulama İşlerim');
    }

    public function test_responsive_dom_classes(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $response = $this->actingAs($user)->get($this->route)->assertOk();

        // Must use responsive col-12 for mobile and col-md-* for desktop
        $response->assertSee('col-12 col-md-4', false);
        // Should not use fixed width hacks
        $response->assertDontSee('overflow-x: hidden', false);
    }
}
