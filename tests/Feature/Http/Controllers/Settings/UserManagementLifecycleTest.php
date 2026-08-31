<?php

namespace Tests\Feature\Http\Controllers\Settings;

use App\Enums\UserCapability;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserManagementLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $target;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

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

        $this->target = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::EMPLOYEE,
            'department_id' => Department::factory()->create(['is_active' => true])->id,
            'must_set_password' => true,
        ]);
    }

    public function test_it_can_view_edit_page()
    {
        $response = $this->actingAs($this->admin)->get(route('settings.users.edit', $this->target));
        $response->assertOk();
        $response->assertViewIs('settings.users.edit');
    }

    public function test_it_can_update_user()
    {
        $response = $this->actingAs($this->admin)->patch(route('settings.users.update', $this->target), [
            'name' => 'Updated Name',
            'email' => 'updated@test.com',
            'role' => UserRole::MANAGER->value,
            'department_id' => $this->target->department_id,
        ]);

        $response->assertRedirect(route('settings.users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $this->target->id,
            'name' => 'Updated Name',
            'email' => 'updated@test.com',
        ]);
    }

    public function test_self_update_is_forbidden()
    {
        $response = $this->actingAs($this->admin)->patch(route('settings.users.update', $this->admin), [
            'name' => 'Self Name',
            'email' => 'self@test.com',
            'role' => UserRole::ADMIN->value,
            'department_id' => null,
        ]);

        $response->assertForbidden();
    }

    public function test_it_can_set_status()
    {
        $response = $this->actingAs($this->admin)->patch(route('settings.users.status', $this->target), [
            'is_active' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $this->target->id,
            'is_active' => false,
        ]);
    }

    public function test_self_set_status_is_forbidden()
    {
        $response = $this->actingAs($this->admin)->patch(route('settings.users.status', $this->admin), [
            'is_active' => false,
        ]);

        $response->assertForbidden();
    }

    public function test_it_can_resend_invitation()
    {
        $response = $this->actingAs($this->admin)->post(route('settings.users.invitation', $this->target));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $this->target->id,
        ]);

        $this->assertNotNull($this->target->fresh()->invitation_sent_at);
    }

    public function test_self_resend_invitation_is_forbidden()
    {
        $this->admin->update(['must_set_password' => true]);

        $response = $this->actingAs($this->admin)->post(route('settings.users.invitation', $this->admin));

        $response->assertForbidden();
    }
}
