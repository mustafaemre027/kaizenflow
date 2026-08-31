<?php

namespace Tests\Feature\Dashboard;

use App\Enums\UserCapability;
use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_dashboard()
    {
        $response = $this->get(route('dashboard.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_without_organization_view_capability_cannot_access_dashboard()
    {
        $user = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.index'));
        $response->assertStatus(403);
    }

    public function test_admin_without_organization_view_capability_cannot_access_dashboard()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard.index'));
        $response->assertStatus(403);
    }

    public function test_user_with_organization_view_capability_can_access_dashboard()
    {
        $user = User::factory()->create([
            'role' => UserRole::EMPLOYEE,
            'is_active' => true,
        ]);

        UserSystemCapabilityGrant::create([
            'user_id' => $user->id,
            'capability' => UserCapability::ORGANIZATION_VIEW,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.index'));
        $response->assertStatus(200);
    }

    public function test_inactive_user_cannot_access_dashboard()
    {
        $user = User::factory()->create([
            'role' => UserRole::MANAGER,
            'is_active' => false,
        ]);

        UserSystemCapabilityGrant::create([
            'user_id' => $user->id,
            'capability' => UserCapability::ORGANIZATION_VIEW,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.index'));
        // Middleware should catch inactive user and redirect to login
        $response->assertRedirect(route('login'));
    }
}
