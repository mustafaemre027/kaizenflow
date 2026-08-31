<?php

namespace Tests\Feature\Settings;

use App\Enums\UserCapability;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceDataManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $department = Department::factory()->create(['is_active' => true]);

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true, 'department_id' => $department->id]);
        $this->employee = User::factory()->create(['role' => UserRole::EMPLOYEE, 'is_active' => true, 'department_id' => $department->id]);

        $this->admin->systemCapabilityGrants()->create(['capability' => UserCapability::ORGANIZATION_VIEW, 'is_active' => true]);
        $this->admin->systemCapabilityGrants()->create(['capability' => UserCapability::ORGANIZATION_MANAGE, 'is_active' => true]);
        $this->admin->systemCapabilityGrants()->create(['capability' => UserCapability::KAIZEN_OPEX_REVIEW, 'is_active' => true]);
    }

    public function test_reference_data_index_auth_checks(): void
    {
        $this->get(route('settings.reference-data.index'))->assertRedirect(route('login'));
        $this->actingAs($this->employee)->get(route('settings.reference-data.index'))->assertStatus(403);

        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'is_active' => true]);
        $this->actingAs($manager)->get(route('settings.reference-data.index'))->assertStatus(403);

        $opex = User::factory()->create(['role' => UserRole::OPEX_SPECIALIST, 'is_active' => true]);
        $this->actingAs($opex)->get(route('settings.reference-data.index'))->assertStatus(403);

        $this->actingAs($this->admin)->get(route('settings.reference-data.index'))->assertStatus(200);
    }

    public function test_admin_can_create_category(): void
    {
        $payload = ['name' => 'Test Kategori', 'code' => 'TEST_CAT'];
        $this->actingAs($this->admin)->post(route('settings.categories.store'), $payload)->assertRedirect();

        $this->assertDatabaseHas('categories', [
            'name' => 'Test Kategori',
            'code' => 'TEST_CAT',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_category_status(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin)->patch(route('settings.categories.status', $category))->assertRedirect();
        $this->assertFalse($category->refresh()->is_active);

        $this->actingAs($this->admin)->patch(route('settings.categories.status', $category))->assertRedirect();
        $this->assertTrue($category->refresh()->is_active);
    }

    public function test_admin_can_create_department(): void
    {
        $payload = ['name' => 'Test Departman', 'code' => 'TEST_DEP'];
        $this->actingAs($this->admin)->post(route('settings.departments.store'), $payload)->assertRedirect();

        $this->assertDatabaseHas('departments', [
            'name' => 'Test Departman',
            'code' => 'TEST_DEP',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_department_status_if_no_active_users(): void
    {
        $department = Department::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin)->patch(route('settings.departments.status', $department))->assertRedirect();
        $this->assertFalse($department->refresh()->is_active);
    }

    public function test_admin_cannot_deactivate_department_with_active_users(): void
    {
        $department = Department::factory()->create(['is_active' => true]);
        User::factory()->create(['department_id' => $department->id, 'is_active' => true]);

        $response = $this->actingAs($this->admin)->patch(route('settings.departments.status', $department));

        $response->assertSessionHas('error');
        $this->assertTrue($department->refresh()->is_active);
    }

    public function test_inactive_categories_and_departments_do_not_appear_in_kaizen_create(): void
    {
        $inactiveCategory = Category::factory()->create(['is_active' => false]);
        $activeCategory = Category::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)->get(route('kaizens.create'));
        $response->assertSee($activeCategory->name);
        $response->assertDontSee($inactiveCategory->name);
    }

    public function test_historical_kaizens_keep_their_relations_when_reference_data_deactivated(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        $department = Department::factory()->create(['is_active' => true]);

        $kaizen = Kaizen::factory()->create([
            'category_id' => $category->id,
            'department_id' => $department->id,
        ]);

        $this->actingAs($this->admin)->patch(route('settings.categories.status', $category));
        $this->actingAs($this->admin)->patch(route('settings.departments.status', $department));

        $this->assertFalse($category->refresh()->is_active);
        $this->assertFalse($department->refresh()->is_active);

        // Relations must remain intact
        $this->assertEquals($category->id, $kaizen->refresh()->category_id);
        $this->assertEquals($department->id, $kaizen->refresh()->department_id);

        // Admin should see them in detail view
        $response = $this->actingAs($this->admin)->get(route('kaizens.show', $kaizen));
        $response->assertSee($category->name);
        $response->assertSee($department->name);
    }
}
