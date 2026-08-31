<?php

namespace Tests\Feature\Http\Controllers\Settings;

use App\Enums\UserCapability;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['is_active' => true]);
        
        UserSystemCapabilityGrant::create([
            'user_id' => $this->admin->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
            'is_active' => true,
        ]);
        UserSystemCapabilityGrant::create([
            'user_id' => $this->admin->id,
            'capability' => UserCapability::ORGANIZATION_VIEW,
            'is_active' => true,
        ]);

        $this->employee = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::EMPLOYEE,
        ]);
    }

    public function test_authorized_user_can_view_index()
    {
        $response = $this->actingAs($this->admin)->get(route('settings.users.index'));
        $response->assertOk();
        $response->assertViewIs('settings.users.index');
    }

    public function test_unauthorized_user_cannot_view_index()
    {
        $response = $this->actingAs($this->employee)->get(route('settings.users.index'));
        $response->assertForbidden();
    }

    public function test_admin_without_capabilities_cannot_view_index()
    {
        $fakeAdmin = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::ADMIN,
        ]);
        
        $response = $this->actingAs($fakeAdmin)->get(route('settings.users.index'));
        $response->assertForbidden();
    }

    public function test_authorized_user_can_view_create()
    {
        $response = $this->actingAs($this->admin)->get(route('settings.users.create'));
        $response->assertOk();
        $response->assertViewIs('settings.users.create');
    }

    public function test_store_creates_user_and_redirects()
    {
        $department = Department::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)->post(route('settings.users.store'), [
            'name' => 'Alice Test',
            'email' => 'alice@test.com',
            'role' => UserRole::EMPLOYEE->value,
            'department_id' => $department->id,
        ]);

        $response->assertRedirect(route('settings.users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'alice@test.com',
            'name' => 'Alice Test',
        ]);
    }

    public function test_store_rejects_missing_department_for_employee()
    {
        $response = $this->actingAs($this->admin)->post(route('settings.users.store'), [
            'name' => 'Alice Test',
            'email' => 'alice@test.com',
            'role' => UserRole::EMPLOYEE->value,
            'department_id' => '',
        ]);

        $response->assertSessionHasErrors(['department_id']);
    }

    public function test_store_rejects_injected_password()
    {
        $department = Department::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)->post(route('settings.users.store'), [
            'name' => 'Bob Test',
            'email' => 'bob@test.com',
            'role' => UserRole::EMPLOYEE->value,
            'department_id' => $department->id,
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors(['password']);
    }
}
