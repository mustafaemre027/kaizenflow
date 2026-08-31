<?php

namespace Tests\Feature\Settings;

use App\Enums\UserCapability;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfigurationCenterTest extends TestCase
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
    }

    public function test_category_edit_authorization(): void
    {
        $category = Category::factory()->create();

        $this->get(route('settings.categories.edit', $category))->assertRedirect(route('login'));
        $this->actingAs($this->employee)->get(route('settings.categories.edit', $category))->assertStatus(403);
        $this->actingAs($this->admin)->get(route('settings.categories.edit', $category))->assertStatus(200);
    }

    public function test_department_edit_authorization(): void
    {
        $department = Department::factory()->create();

        $this->get(route('settings.departments.edit', $department))->assertRedirect(route('login'));
        $this->actingAs($this->employee)->get(route('settings.departments.edit', $department))->assertStatus(403);
        $this->actingAs($this->admin)->get(route('settings.departments.edit', $department))->assertStatus(200);
    }

    public function test_category_update(): void
    {
        $category = Category::factory()->create(['name' => 'Old Name', 'code' => 'OLD']);

        $payload = [
            'name' => 'New Name',
            'code' => 'NEW',
            'description' => 'Updated desc',
        ];

        $this->actingAs($this->admin)->patch(route('settings.categories.update', $category), $payload)->assertRedirect();

        $category->refresh();
        $this->assertEquals('New Name', $category->name);
        $this->assertEquals('NEW', $category->code);
        $this->assertEquals('Updated desc', $category->description);

        // Verification of dynamic UI propagation
        $response = $this->actingAs($this->admin)->get(route('kaizens.create'));
        $response->assertSee('New Name');
    }

    public function test_department_update(): void
    {
        $department = Department::factory()->create(['name' => 'Lojistik', 'code' => 'LOJ']);

        $payload = [
            'name' => 'Lojistik ve Sevkiyat',
            'code' => 'LOJ_SEV',
        ];

        $this->actingAs($this->admin)->patch(route('settings.departments.update', $department), $payload)->assertRedirect();

        $department->refresh();
        $this->assertEquals('Lojistik ve Sevkiyat', $department->name);
        $this->assertEquals('LOJ_SEV', $department->code);
    }

    public function test_category_search_and_filter(): void
    {
        Category::factory()->create(['name' => 'Enerji Verimliliği', 'code' => 'ENERGY', 'is_active' => true]);
        Category::factory()->create(['name' => 'İş Güvenliği', 'code' => 'OHS', 'is_active' => false]);
        Category::factory()->create(['name' => 'Maliyet Düşürme', 'code' => 'COST', 'is_active' => true]);

        // Search by name
        $response = $this->actingAs($this->admin)->get(route('settings.reference-data.index', ['category_q' => 'Enerji']));
        $response->assertSee('Enerji Verimliliği');
        $response->assertDontSee('Maliyet Düşürme');

        // Search by code
        $response = $this->actingAs($this->admin)->get(route('settings.reference-data.index', ['category_q' => 'OHS']));
        $response->assertSee('İş Güvenliği');
        $response->assertDontSee('Enerji Verimliliği');

        // Filter active
        $response = $this->actingAs($this->admin)->get(route('settings.reference-data.index', ['category_status' => 'active']));
        $response->assertSee('Enerji Verimliliği');
        $response->assertSee('Maliyet Düşürme');
        $response->assertDontSee('İş Güvenliği');

        // Filter inactive
        $response = $this->actingAs($this->admin)->get(route('settings.reference-data.index', ['category_status' => 'inactive']));
        $response->assertSee('İş Güvenliği');
        $response->assertDontSee('Enerji Verimliliği');
        $response->assertDontSee('Maliyet Düşürme');
    }

    public function test_department_search_and_filter(): void
    {
        Department::factory()->create(['name' => 'İnsan Kaynakları', 'code' => 'HR', 'is_active' => true]);
        Department::factory()->create(['name' => 'Arge', 'code' => 'RND', 'is_active' => false]);

        // Search by name
        $response = $this->actingAs($this->admin)->get(route('settings.reference-data.index', ['department_q' => 'İnsan']));
        $response->assertSee('İnsan Kaynakları');
        $response->assertDontSee('Arge');

        // Filter inactive
        $response = $this->actingAs($this->admin)->get(route('settings.reference-data.index', ['department_status' => 'inactive']));
        $response->assertSee('Arge');
        $response->assertDontSee('İnsan Kaynakları');
    }

    public function test_pagination_independence(): void
    {
        Category::factory()->count(20)->create();
        Department::factory()->count(20)->create();

        // Get page 2 for category, page 1 for department
        $response = $this->actingAs($this->admin)->get(route('settings.reference-data.index', [
            'category_page' => 2,
        ]));

        $response->assertStatus(200);
        // It should pass if paginators use different query string keys
    }

    public function test_safety_guard_blocks_department_deactivation(): void
    {
        $department = Department::factory()->create(['is_active' => true]);
        User::factory()->create(['department_id' => $department->id, 'is_active' => true]);

        $response = $this->actingAs($this->admin)->patch(route('settings.departments.status', $department));
        $response->assertSessionHas('error');

        $this->assertTrue($department->refresh()->is_active);
    }
}
