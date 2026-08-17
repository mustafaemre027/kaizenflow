<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use App\Models\Department;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReferenceDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_departments_and_categories_tables_exist_with_columns(): void
    {
        $this->assertTrue(Schema::hasTable('departments'));
        $this->assertTrue(Schema::hasColumns('departments', [
            'id', 'name', 'code', 'description', 'is_active', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('categories'));
        $this->assertTrue(Schema::hasColumns('categories', [
            'id', 'name', 'code', 'description', 'is_active', 'created_at', 'updated_at',
        ]));
    }

    public function test_department_can_be_created_and_cast_is_active(): void
    {
        $department = Department::create([
            'name' => 'Test Department',
            'code' => 'TEST-DEPT',
            'description' => 'Test Description',
            'is_active' => 1,
        ]);

        $this->assertDatabaseHas('departments', ['code' => 'TEST-DEPT']);
        $this->assertTrue($department->is_active);
        $this->assertIsBool($department->is_active);
    }

    public function test_department_active_scope_excludes_inactive_records(): void
    {
        Department::create(['name' => 'Active Dept', 'code' => 'ACT', 'is_active' => true]);
        Department::create(['name' => 'Inactive Dept', 'code' => 'INACT', 'is_active' => false]);

        $activeCount = Department::active()->count();
        $this->assertSame(1, $activeCount);
    }

    public function test_department_code_must_be_unique(): void
    {
        Department::create(['name' => 'Dept 1', 'code' => 'UNIQUE']);

        $this->expectException(QueryException::class);
        Department::create(['name' => 'Dept 2', 'code' => 'UNIQUE']);
    }

    public function test_category_can_be_created_and_cast_is_active(): void
    {
        $category = Category::create([
            'name' => 'Test Category',
            'code' => 'TEST-CAT',
            'description' => 'Test Description',
            'is_active' => 1,
        ]);

        $this->assertDatabaseHas('categories', ['code' => 'TEST-CAT']);
        $this->assertTrue($category->is_active);
        $this->assertIsBool($category->is_active);
    }

    public function test_category_active_scope_excludes_inactive_records(): void
    {
        Category::create(['name' => 'Active Cat', 'code' => 'ACT-CAT', 'is_active' => true]);
        Category::create(['name' => 'Inactive Cat', 'code' => 'INACT-CAT', 'is_active' => false]);

        $activeCount = Category::active()->count();
        $this->assertSame(1, $activeCount);
    }

    public function test_category_code_must_be_unique(): void
    {
        Category::create(['name' => 'Cat 1', 'code' => 'UNIQUE-CAT']);

        $this->expectException(QueryException::class);
        Category::create(['name' => 'Cat 2', 'code' => 'UNIQUE-CAT']);
    }
}
