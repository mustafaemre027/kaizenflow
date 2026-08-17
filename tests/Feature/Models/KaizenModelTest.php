<?php

namespace Tests\Feature\Models;

use App\Enums\KaizenPriority;
use App\Enums\KaizenStatus;
use App\Models\Category;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class KaizenModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_synthetic_record_via_factory(): void
    {
        $kaizen = Kaizen::factory()->create();

        $this->assertDatabaseHas('kaizens', [
            'id' => $kaizen->id,
            'code' => $kaizen->code,
        ]);
        $this->assertNotNull($kaizen->title);
        $this->assertNotNull($kaizen->current_situation);
    }

    public function test_it_has_default_draft_status(): void
    {
        $kaizen = Kaizen::factory()->create();

        $this->assertEquals(KaizenStatus::DRAFT, $kaizen->status);
    }

    public function test_it_casts_status_and_priority_to_enums(): void
    {
        $kaizen = Kaizen::factory()->withPriority(KaizenPriority::HIGH)->withStatus(KaizenStatus::APPROVED)->create();

        $this->assertInstanceOf(KaizenStatus::class, $kaizen->status);
        $this->assertEquals(KaizenStatus::APPROVED, $kaizen->status);

        $this->assertInstanceOf(KaizenPriority::class, $kaizen->priority);
        $this->assertEquals(KaizenPriority::HIGH, $kaizen->priority);
    }

    public function test_it_casts_dates_correctly(): void
    {
        $kaizen = Kaizen::factory()->create([
            'target_date' => '2026-10-10',
            'submitted_at' => now(),
        ]);

        $this->assertInstanceOf(Carbon::class, $kaizen->target_date);
        $this->assertInstanceOf(Carbon::class, $kaizen->submitted_at);
        $this->assertEquals('2026-10-10', $kaizen->target_date->format('Y-m-d'));
    }

    public function test_it_belongs_to_creator(): void
    {
        $user = User::factory()->create();
        $kaizen = Kaizen::factory()->create(['creator_user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $kaizen->creator);
        $this->assertEquals($user->id, $kaizen->creator->id);
    }

    public function test_it_belongs_to_assigned_user_and_can_be_null(): void
    {
        $kaizenNull = Kaizen::factory()->create(['assigned_user_id' => null]);
        $this->assertNull($kaizenNull->assigned_user_id);
        $this->assertNull($kaizenNull->assignedUser);

        $user = User::factory()->create();
        $kaizen = Kaizen::factory()->assignedTo($user)->create();

        $this->assertInstanceOf(User::class, $kaizen->assignedUser);
        $this->assertEquals($user->id, $kaizen->assignedUser->id);
    }

    public function test_it_belongs_to_department_and_category(): void
    {
        $department = Department::factory()->create();
        $category = Category::factory()->create();

        $kaizen = Kaizen::factory()->create([
            'department_id' => $department->id,
            'category_id' => $category->id,
        ]);

        $this->assertInstanceOf(Department::class, $kaizen->department);
        $this->assertEquals($department->id, $kaizen->department->id);

        $this->assertInstanceOf(Category::class, $kaizen->category);
        $this->assertEquals($category->id, $kaizen->category->id);
    }

    public function test_user_has_inverse_relationships(): void
    {
        $user = User::factory()->create();
        $createdKaizen = Kaizen::factory()->create(['creator_user_id' => $user->id]);
        $assignedKaizen = Kaizen::factory()->assignedTo($user)->create();

        $this->assertTrue($user->createdKaizens->contains($createdKaizen));
        $this->assertTrue($user->assignedKaizens->contains($assignedKaizen));
    }

    public function test_department_and_category_have_inverse_relationships(): void
    {
        $department = Department::factory()->create();
        $category = Category::factory()->create();

        $kaizen = Kaizen::factory()->create([
            'department_id' => $department->id,
            'category_id' => $category->id,
        ]);

        $this->assertTrue($department->kaizens->contains($kaizen));
        $this->assertTrue($category->kaizens->contains($kaizen));
    }

    public function test_query_scopes(): void
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $department = Department::factory()->create();

        $kaizen = Kaizen::factory()
            ->assignedTo($assignee)
            ->withStatus(KaizenStatus::SUBMITTED)
            ->create([
                'creator_user_id' => $creator->id,
                'department_id' => $department->id,
            ]);

        Kaizen::factory()->count(2)->create();

        $this->assertCount(1, Kaizen::forCreator($creator)->get());
        $this->assertCount(1, Kaizen::assignedTo($assignee)->get());
        $this->assertCount(1, Kaizen::forDepartment($department)->get());
        $this->assertCount(1, Kaizen::withStatus(KaizenStatus::SUBMITTED)->get());
    }

    public function test_code_must_be_unique(): void
    {
        Kaizen::factory()->create(['code' => 'KF-UNIQUE-1']);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Integrity constraint violation');

        Kaizen::factory()->create(['code' => 'KF-UNIQUE-1']);
    }

    public function test_sensitive_fields_cannot_be_mass_assigned(): void
    {
        $kaizen = new Kaizen([
            'code' => 'SHOULD-NOT-FILL',
            'status' => KaizenStatus::APPROVED->value,
            'creator_user_id' => 999,
            'actual_result' => 'Hacked',
            'title' => 'Safe Title',
        ]);

        $this->assertNull($kaizen->code);
        $this->assertNull($kaizen->status);
        $this->assertNull($kaizen->creator_user_id);
        $this->assertNull($kaizen->actual_result);
        $this->assertEquals('Safe Title', $kaizen->title);
    }

    public function test_foreign_key_constraints_restrict_deletion(): void
    {
        $user = User::factory()->create();
        Kaizen::factory()->create(['creator_user_id' => $user->id]);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Integrity constraint violation');

        $user->delete();
    }
}
