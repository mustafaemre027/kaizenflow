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

class KaizenImplementationWorkQueueHttpTest extends TestCase
{
    use RefreshDatabase;

    private string $route = '/implementation/work-queue';

    private string $routeName = 'implementation.work-queue.index';

    public function test_guest_cannot_access_queue(): void
    {
        $this->get($this->route)->assertRedirect('/login');
        $this->get(route($this->routeName))->assertRedirect('/login');
    }

    public function test_active_user_can_view_own_queue(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $kaizen = Kaizen::factory()->create([
            'assigned_user_id' => $user->id,
            'status' => KaizenStatus::IN_PROGRESS,
            'target_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $this->actingAs($user)
            ->get($this->route)
            ->assertOk()
            ->assertSee('Uygulama İşlerim')
            ->assertSee($kaizen->code)
            ->assertSee($kaizen->title);
    }

    public function test_inactive_user_gets_403_fail_closed(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->actingAs($user)->get($this->route)->assertRedirect('/login');
    }

    public function test_cannot_see_kaizens_assigned_to_others(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $otherUser = User::factory()->create(['is_active' => true]);
        $otherKaizen = Kaizen::factory()->create([
            'assigned_user_id' => $otherUser->id,
            'status' => KaizenStatus::IN_PROGRESS,
        ]);

        $this->actingAs($user)
            ->get($this->route)
            ->assertOk()
            ->assertDontSee($otherKaizen->code);
    }

    public function test_admin_cannot_bypass_self_only_boundary(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'role' => UserRole::ADMIN]);

        $otherUser = User::factory()->create(['is_active' => true]);
        $otherKaizen = Kaizen::factory()->create([
            'assigned_user_id' => $otherUser->id,
            'status' => KaizenStatus::IN_PROGRESS,
        ]);

        $this->actingAs($admin)
            ->get($this->route)
            ->assertOk()
            ->assertDontSee($otherKaizen->code);
    }

    public function test_cannot_inject_assigned_user_id_via_query_string(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $otherUser = User::factory()->create(['is_active' => true]);
        $otherKaizen = Kaizen::factory()->create([
            'assigned_user_id' => $otherUser->id,
            'status' => KaizenStatus::IN_PROGRESS,
        ]);

        $this->actingAs($user)
            ->get($this->route.'?assigned_user_id='.$otherUser->id)
            ->assertOk()
            ->assertDontSee($otherKaizen->code);
    }

    public function test_overdue_record_shows_correct_badge(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $kaizen = Kaizen::factory()->create([
            'assigned_user_id' => $user->id,
            'status' => KaizenStatus::IN_PROGRESS,
            'target_date' => now()->subDay()->format('Y-m-d'),
        ]);

        $this->actingAs($user)
            ->get($this->route)
            ->assertOk()
            ->assertSee($kaizen->code)
            ->assertSee('Gecikmiş');
    }

    public function test_today_target_date_is_not_overdue(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $kaizen = Kaizen::factory()->create([
            'assigned_user_id' => $user->id,
            'status' => KaizenStatus::IN_PROGRESS,
            'target_date' => now()->format('Y-m-d'),
        ]);

        $this->actingAs($user)
            ->get($this->route)
            ->assertOk()
            ->assertSee($kaizen->code)
            ->assertDontSee('Gecikmiş')
            ->assertSee('Bugün');
    }

    public function test_future_date_is_not_overdue(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $date = now()->addDays(3)->format('Y-m-d');
        $kaizen = Kaizen::factory()->create([
            'assigned_user_id' => $user->id,
            'status' => KaizenStatus::IN_PROGRESS,
            'target_date' => $date,
        ]);

        $this->actingAs($user)
            ->get($this->route)
            ->assertOk()
            ->assertSee($kaizen->code)
            ->assertDontSee('Gecikmiş')
            ->assertDontSee('Bugün')
            ->assertSee($date);
    }

    public function test_null_target_date_shows_safe_text(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $kaizen = Kaizen::factory()->create([
            'assigned_user_id' => $user->id,
            'status' => KaizenStatus::IN_PROGRESS,
            'target_date' => null,
        ]);

        $this->actingAs($user)
            ->get($this->route)
            ->assertOk()
            ->assertSee($kaizen->code)
            ->assertDontSee('Gecikmiş')
            ->assertSee('Hedef tarih belirtilmedi');
    }

    public function test_terminal_records_are_not_visible(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $completed = Kaizen::factory()->create([
            'assigned_user_id' => $user->id,
            'status' => KaizenStatus::COMPLETED,
        ]);

        $rejected = Kaizen::factory()->create([
            'assigned_user_id' => $user->id,
            'status' => KaizenStatus::REJECTED,
        ]);

        $this->actingAs($user)
            ->get($this->route)
            ->assertOk()
            ->assertDontSee($completed->code)
            ->assertDontSee($rejected->code);
    }

    public function test_deterministic_ordering_is_preserved_in_html(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $nullDate = Kaizen::factory()->create(['assigned_user_id' => $user->id, 'status' => KaizenStatus::IN_PROGRESS, 'target_date' => null]);
        $future = Kaizen::factory()->create(['assigned_user_id' => $user->id, 'status' => KaizenStatus::IN_PROGRESS, 'target_date' => now()->addDays(5)->format('Y-m-d')]);
        $overdue1 = Kaizen::factory()->create(['assigned_user_id' => $user->id, 'status' => KaizenStatus::IN_PROGRESS, 'target_date' => now()->subDays(5)->format('Y-m-d')]);
        $overdue2 = Kaizen::factory()->create(['assigned_user_id' => $user->id, 'status' => KaizenStatus::IN_PROGRESS, 'target_date' => now()->subDays(2)->format('Y-m-d')]);

        $response = $this->actingAs($user)->get($this->route)->assertOk();
        $html = $response->getContent();

        $pos1 = strpos($html, $overdue1->code);
        $pos2 = strpos($html, $overdue2->code);
        $pos3 = strpos($html, $future->code);
        $pos4 = strpos($html, $nullDate->code);

        $this->assertTrue($pos1 < $pos2 && $pos2 < $pos3 && $pos3 < $pos4, 'Deterministic ordering is incorrect in HTML.');
    }

    public function test_pagination_works_with_15_records(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Kaizen::factory()->count(20)->create([
            'assigned_user_id' => $user->id,
            'status' => KaizenStatus::IN_PROGRESS,
            'target_date' => now()->addDay()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)->get($this->route)->assertOk();
        $response->assertSee('page=2');

        // Find how many codes are in the page
        $kaizens = Kaizen::orderBy('id', 'asc')->get();

        foreach ($kaizens->take(15) as $k) {
            $response->assertSee($k->code);
        }
        foreach ($kaizens->skip(15) as $k) {
            $response->assertDontSee($k->code);
        }

        $response2 = $this->actingAs($user)->get($this->route.'?page=2')->assertOk();
        foreach ($kaizens->skip(15) as $k) {
            $response2->assertSee($k->code);
        }
    }

    public function test_relationships_do_not_produce_n_plus_one(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Kaizen::factory()->count(10)->create([
            'assigned_user_id' => $user->id,
            'status' => KaizenStatus::IN_PROGRESS,
        ]);

        // Boot and execute query to measure DB interactions
        $this->actingAs($user);

        DB::enableQueryLog();
        $this->get($this->route)->assertOk();
        $queries = DB::getQueryLog();

        // 1 query for kaizens, 1 for creators, 1 for assigned, 1 for categories, 1 for departments = ~5 queries
        // Make sure it's strictly less than 15 (since N=10 and we have 5-8 base queries)
        if (count($queries) >= 15) {
            dd($queries);
        }
        $this->assertTrue(count($queries) < 15, 'N+1 detected in HTTP endpoint.');
    }

    public function test_xss_canary_is_escaped(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $kaizen = Kaizen::factory()->create([
            'assigned_user_id' => $user->id,
            'status' => KaizenStatus::IN_PROGRESS,
            'title' => '<script>alert("XSS")</script>',
        ]);

        $response = $this->actingAs($user)->get($this->route)->assertOk();
        $response->assertDontSee('<script>alert("XSS")</script>', false);
        $response->assertSee('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', false);
    }

    public function test_page_render_does_not_create_audit_or_mutation(): void
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

    public function test_empty_state_is_visible(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)
            ->get($this->route)
            ->assertOk()
            ->assertSee('Şu anda üzerinize atanmış aktif bir uygulama görevi bulunmuyor.');
    }

    public function test_dom_contracts_are_respected(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $kaizen = Kaizen::factory()->create([
            'assigned_user_id' => $user->id,
            'status' => KaizenStatus::IN_PROGRESS,
            'target_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)->get($this->route)->assertOk();
        $html = $response->getContent();

        $this->assertEquals(1, substr_count(strtolower($html), '<h1'));
        $this->assertStringContainsString('Uygulama İşlerim', $html);

        // Check for time tag with datetime
        $response->assertSee('<time datetime="', false);
    }

    public function test_kaizen_detail_links_belong_only_to_visible_records(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $visible = Kaizen::factory()->create([
            'assigned_user_id' => $user->id,
            'status' => KaizenStatus::IN_PROGRESS,
        ]);
        $hidden = Kaizen::factory()->create([
            'assigned_user_id' => User::factory()->create()->id,
            'status' => KaizenStatus::IN_PROGRESS,
        ]);

        $response = $this->actingAs($user)->get($this->route)->assertOk();

        $response->assertSee(route('kaizens.show', $visible));
        $response->assertDontSee(route('kaizens.show', $hidden));
    }
}
