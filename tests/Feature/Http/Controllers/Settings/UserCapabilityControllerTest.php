<?php

namespace Tests\Feature\Http\Controllers\Settings;

use App\Enums\UserCapability;
use App\Models\Department;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCapabilityControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $target;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->target = User::factory()->create(['is_active' => true]);
        $this->department = Department::factory()->create(['is_active' => true]);

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
        UserSystemCapabilityGrant::create([
            'user_id' => $this->admin->id,
            'capability' => UserCapability::ORGANIZATION_MANAGE,
            'is_active' => true,
        ]);
    }

    public function test_it_displays_capabilities_page()
    {
        $response = $this->actingAs($this->admin)->get(route('settings.users.capabilities', $this->target));

        $response->assertStatus(200);
        $response->assertViewIs('settings.users.capabilities');
        $response->assertViewHas('targetUser', $this->target);
    }

    public function test_it_rejects_capability_page_without_auth_manage_and_org_view()
    {
        $employee = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($employee)->get(route('settings.users.capabilities', $this->target));

        $response->assertStatus(403);
    }

    public function test_it_allows_self_to_view_capabilities()
    {
        $response = $this->actingAs($this->admin)->get(route('settings.users.capabilities', $this->admin));

        $response->assertStatus(200);
        $response->assertSee('Kendi yetkilerinizi yalnızca görüntüleyebilirsiniz');
    }

    public function test_it_sets_system_capability_grant()
    {
        // Admin needs the exact capability to grant it to others
        UserSystemCapabilityGrant::create([
            'user_id' => $this->admin->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('settings.users.capabilities.system', $this->target), [
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW->value,
            'is_active' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Sistem yetkisi güncellendi.');

        $this->assertDatabaseHas('user_system_capability_grants', [
            'user_id' => $this->target->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW->value,
            'is_active' => true,
        ]);
    }

    public function test_it_sets_department_capability_grant()
    {
        $response = $this->actingAs($this->admin)->patch(route('settings.users.capabilities.department', $this->target), [
            'department_id' => $this->department->id,
            'capability' => UserCapability::KAIZEN_DEPARTMENT_APPROVE->value,
            'is_active' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Departman yetkisi güncellendi.');

        $this->assertDatabaseHas('user_capability_grants', [
            'user_id' => $this->target->id,
            'department_id' => $this->department->id,
            'capability' => UserCapability::KAIZEN_DEPARTMENT_APPROVE->value,
            'is_active' => true,
        ]);
    }

    public function test_it_rejects_injecting_department_capability_into_system_endpoint()
    {
        $response = $this->actingAs($this->admin)->patch(route('settings.users.capabilities.system', $this->target), [
            'capability' => UserCapability::KAIZEN_DEPARTMENT_APPROVE->value, // This is a DEPARTMENT capability
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors(['capability']);
    }

    public function test_it_rejects_injecting_system_capability_into_department_endpoint()
    {
        $response = $this->actingAs($this->admin)->patch(route('settings.users.capabilities.department', $this->target), [
            'department_id' => $this->department->id,
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW->value, // This is a SYSTEM capability
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors(['capability']);
    }

    public function test_it_rejects_self_mutation()
    {
        $response = $this->actingAs($this->admin)->patch(route('settings.users.capabilities.system', $this->admin), [
            'capability' => UserCapability::KAIZEN_OPEX_REVIEW->value,
            'is_active' => 1,
        ]);

        $response->assertStatus(403);
    }
}
