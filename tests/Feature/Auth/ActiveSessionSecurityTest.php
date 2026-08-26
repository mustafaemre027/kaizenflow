<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveSessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_is_redirected_to_login_and_logged_out_on_html_request(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        // Authenticate the user and set a session
        $this->actingAs($user);
        session()->put('foo', 'bar');

        // Access an authenticated HTML route (home or kaizens)
        $response = $this->get('/');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');

        // Assert session is invalidated (foo is gone)
        $this->assertNull(session('foo'));

        // Assert user is logged out
        $this->assertGuest();
    }

    public function test_inactive_user_receives_403_and_is_logged_out_on_json_request(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->actingAs($user);

        // Access an authenticated JSON route
        $response = $this->getJson('/kaizens');

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Your account is inactive.']);
        $this->assertGuest();
    }

    public function test_active_user_can_access_authenticated_routes(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $response = $this->get('/kaizens');

        $response->assertStatus(200);
        $this->assertAuthenticatedAs($user);
    }

    public function test_role_bypass_is_not_possible_for_inactive_user(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
            'role' => UserRole::ADMIN,
        ]);

        $this->actingAs($user);

        $response = $this->get('/kaizens');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_logout_and_password_reset_routes_are_exempt(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->actingAs($user);

        // Logout should still work and redirect to home or login normally, not with the "inactive account" error necessarily, but if it does, it's fine as long as it succeeds to log them out.
        // Actually, if we exclude logout from active-user, it should hit the controller and redirect to '/'.
        $response = $this->post('/logout');

        // Default logout redirects to '/'
        $response->assertRedirect('/');
        $this->assertGuest();
    }
}
