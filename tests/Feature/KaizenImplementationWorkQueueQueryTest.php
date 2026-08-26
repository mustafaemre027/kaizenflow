<?php

namespace Tests\Feature;

use App\Enums\KaizenPriority;
use App\Enums\KaizenStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\User;
use App\Queries\KaizenImplementationWorkQueueQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KaizenImplementationWorkQueueQueryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Department $department;

    private Category $category;

    private KaizenImplementationWorkQueueQuery $query;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create();
        $this->category = Category::factory()->create();
        $this->user = User::factory()->create(['role' => UserRole::EMPLOYEE, 'department_id' => $this->department->id, 'is_active' => true]);

        $this->query = new KaizenImplementationWorkQueueQuery;
    }

    private function createKaizen(array $attributes = []): Kaizen
    {
        return Kaizen::factory()->create(array_merge([
            'department_id' => $this->department->id,
            'category_id' => $this->category->id,
            'creator_user_id' => $this->user->id,
            'assigned_user_id' => $this->user->id,
            'title' => 'Test Kaizen',
            'current_situation' => 'Cur',
            'proposed_situation' => 'Prop',
            'expected_benefit' => 'Ben',
            'priority' => KaizenPriority::MEDIUM,
            'status' => KaizenStatus::APPROVED,
            'target_date' => now()->addDays(5),
        ], $attributes));
    }

    public function test_it_returns_only_kaizens_assigned_to_actor()
    {
        $otherUser = User::factory()->create(['role' => UserRole::EMPLOYEE, 'is_active' => true]);

        $mine = $this->createKaizen(['assigned_user_id' => $this->user->id]);
        $other = $this->createKaizen(['assigned_user_id' => $otherUser->id]);

        $results = $this->query->execute($this->user)->items();

        $this->assertCount(1, $results);
        $this->assertEquals($mine->id, $results[0]->id);
    }

    public function test_admin_cannot_bypass_personal_queue()
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);
        $otherUser = User::factory()->create(['role' => UserRole::EMPLOYEE, 'is_active' => true]);

        $this->createKaizen(['assigned_user_id' => $otherUser->id]);
        $this->createKaizen(['assigned_user_id' => $admin->id]);

        $results = $this->query->execute($admin)->items();

        $this->assertCount(1, $results);
        $this->assertEquals($admin->id, $results[0]->assigned_user_id);
    }

    public function test_inactive_user_returns_empty_results_fail_closed()
    {
        $inactive = User::factory()->create(['role' => UserRole::EMPLOYEE, 'is_active' => false]);
        $this->createKaizen(['assigned_user_id' => $inactive->id]);

        $results = $this->query->execute($inactive)->items();

        $this->assertCount(0, $results);
    }

    public function test_terminal_statuses_are_excluded()
    {
        $this->createKaizen(['status' => KaizenStatus::COMPLETED]);
        $this->createKaizen(['status' => KaizenStatus::REJECTED]);
        $approved = $this->createKaizen(['status' => KaizenStatus::APPROVED]);
        $inProgress = $this->createKaizen(['status' => KaizenStatus::IN_PROGRESS]);

        $results = $this->query->execute($this->user)->items();

        $this->assertCount(2, $results);
        $ids = array_map(fn ($k) => $k->id, $results);
        $this->assertContains($approved->id, $ids);
        $this->assertContains($inProgress->id, $ids);
    }

    public function test_overdue_calculation_dynamic_based_on_target_date_and_timezone()
    {
        // Setup timezone boundary testing
        Carbon::setTestNow(Carbon::create(2026, 8, 20, 0, 0, 0, config('app.timezone')));

        $yesterday = $this->createKaizen(['target_date' => Carbon::now()->subDay()]);
        $today = $this->createKaizen(['target_date' => Carbon::now()]);
        $tomorrow = $this->createKaizen(['target_date' => Carbon::now()->addDay()]);
        $noDate = $this->createKaizen(['target_date' => null]);

        $results = $this->query->execute($this->user)->items();
        $this->assertCount(4, $results);

        $keyed = collect($results)->keyBy('id');

        $this->assertTrue((bool) $keyed[$yesterday->id]->is_overdue, 'Yesterday should be overdue');
        $this->assertFalse((bool) $keyed[$today->id]->is_overdue, 'Today is not overdue');
        $this->assertFalse((bool) $keyed[$tomorrow->id]->is_overdue, 'Tomorrow is not overdue');
        $this->assertFalse((bool) $keyed[$noDate->id]->is_overdue, 'No date is not overdue');
    }

    public function test_ordering_is_deterministic_by_overdue_then_target_date_then_id()
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 20, 0, 0, 0, config('app.timezone')));

        // 1. Overdue with target date 3 days ago (ID 1)
        $overdueOld = $this->createKaizen(['target_date' => Carbon::now()->subDays(3)]);

        // 2. Overdue with target date 1 day ago (ID 2)
        $overdueRecent = $this->createKaizen(['target_date' => Carbon::now()->subDays(1)]);

        // 3. Not overdue, target date today (ID 3)
        $today = $this->createKaizen(['target_date' => Carbon::now()]);

        // 4. Not overdue, target date today, higher ID (ID 4)
        $todayHigherId = $this->createKaizen(['target_date' => Carbon::now()]);

        // 5. Not overdue, target date tomorrow (ID 5)
        $tomorrow = $this->createKaizen(['target_date' => Carbon::now()->addDay()]);

        // 6. Not overdue, no target date (ID 6)
        $noDate = $this->createKaizen(['target_date' => null]);

        $results = $this->query->execute($this->user)->items();

        $this->assertCount(6, $results);

        $this->assertEquals($overdueOld->id, $results[0]->id);
        $this->assertEquals($overdueRecent->id, $results[1]->id);
        $this->assertEquals($today->id, $results[2]->id);
        $this->assertEquals($todayHigherId->id, $results[3]->id);
        $this->assertEquals($tomorrow->id, $results[4]->id);
        $this->assertEquals($noDate->id, $results[5]->id);
    }

    public function test_pagination_works()
    {
        for ($i = 0; $i < 25; $i++) {
            $this->createKaizen(['target_date' => now()->addDays($i)]);
        }

        $paginator = $this->query->execute($this->user, 15);
        $this->assertCount(15, $paginator->items());
        $this->assertEquals(25, $paginator->total());
        $this->assertEquals(2, $paginator->lastPage());
    }

    public function test_eager_loading_and_n_plus_one_prevention()
    {
        $this->createKaizen();
        $this->createKaizen();
        $this->createKaizen();

        DB::enableQueryLog();

        $results = $this->query->execute($this->user)->items();

        foreach ($results as $kaizen) {
            $this->assertNotNull($kaizen->creator);
            $this->assertNotNull($kaizen->assignedUser);
            $this->assertNotNull($kaizen->department);
            $this->assertNotNull($kaizen->category);
        }

        // Count query + Main query + 4 relation queries
        $this->assertLessThanOrEqual(6, count(DB::getQueryLog()));

        DB::disableQueryLog();
    }

    public function test_query_is_read_only_and_does_not_mutate_db_or_audit()
    {
        $this->createKaizen();

        $initialAuditCount = DB::table('audit_logs')->count();
        $initialKaizenCount = DB::table('kaizens')->count();

        $this->query->execute($this->user)->items();

        $this->assertEquals($initialAuditCount, DB::table('audit_logs')->count());
        $this->assertEquals($initialKaizenCount, DB::table('kaizens')->count());
    }
}
