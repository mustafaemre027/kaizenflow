<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendingInvitationLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_ready_user_can_login(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'must_set_password' => false,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/');
    }

    public function test_pending_invitation_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'must_set_password' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email' => __('auth.failed')]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
            'must_set_password' => false,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email' => __('auth.failed')]);
    }
}
