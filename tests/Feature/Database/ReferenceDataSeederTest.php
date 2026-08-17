<?php

namespace Tests\Feature\Database;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_seeder_creates_expected_records(): void
    {
        $this->seed(DepartmentSeeder::class);

        $this->assertDatabaseCount('departments', 5);
        $this->assertDatabaseHas('departments', ['code' => 'OPS', 'is_active' => 1]);
        $this->assertDatabaseHas('departments', ['code' => 'QLT', 'is_active' => 1]);
        $this->assertDatabaseHas('departments', ['code' => 'MNT', 'is_active' => 1]);
        $this->assertDatabaseHas('departments', ['code' => 'LOG', 'is_active' => 1]);
        $this->assertDatabaseHas('departments', ['code' => 'IT', 'is_active' => 1]);
    }

    public function test_category_seeder_creates_expected_records(): void
    {
        $this->seed(CategorySeeder::class);

        $this->assertDatabaseCount('categories', 6);
        $this->assertDatabaseHas('categories', ['code' => 'PROCESS', 'is_active' => 1]);
        $this->assertDatabaseHas('categories', ['code' => 'QUALITY', 'is_active' => 1]);
        $this->assertDatabaseHas('categories', ['code' => 'SAFETY', 'is_active' => 1]);
        $this->assertDatabaseHas('categories', ['code' => 'COST', 'is_active' => 1]);
        $this->assertDatabaseHas('categories', ['code' => 'TIME', 'is_active' => 1]);
        $this->assertDatabaseHas('categories', ['code' => 'ENV', 'is_active' => 1]);
    }

    public function test_seeders_are_idempotent(): void
    {
        $this->seed(DepartmentSeeder::class);
        $this->seed(DepartmentSeeder::class);
        $this->assertDatabaseCount('departments', 5);

        $this->seed(CategorySeeder::class);
        $this->seed(CategorySeeder::class);
        $this->assertDatabaseCount('categories', 6);
    }

    public function test_department_factory_creates_valid_record(): void
    {
        $department = Department::factory()->create();

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
        $this->assertTrue($department->is_active);
    }

    public function test_department_factory_inactive_state(): void
    {
        $department = Department::factory()->inactive()->create();

        $this->assertFalse($department->is_active);
    }

    public function test_category_factory_creates_valid_record(): void
    {
        $category = Category::factory()->create();

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertTrue($category->is_active);
    }

    public function test_category_factory_inactive_state(): void
    {
        $category = Category::factory()->inactive()->create();

        $this->assertFalse($category->is_active);
    }

    public function test_user_factory_creates_default_user(): void
    {
        $user = User::factory()->create();

        $this->assertSame(UserRole::EMPLOYEE, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertNull($user->department_id);
    }

    public function test_user_factory_with_role_state(): void
    {
        $user = User::factory()->withRole(UserRole::MANAGER)->create();

        $this->assertSame(UserRole::MANAGER, $user->role);
    }

    public function test_user_factory_for_department_state(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->forDepartment($department)->create();

        $this->assertEquals($department->id, $user->department_id);
    }

    public function test_user_factory_inactive_state(): void
    {
        $user = User::factory()->inactive()->create();

        $this->assertFalse($user->is_active);
    }

    public function test_database_seeder_does_not_create_users(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 0);
    }
}
