<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_active_user_receives_reset_email_and_neutral_response(): void
    {
        Notification::fake();

        $user = User::factory()->create(['is_active' => true]);

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_inactive_user_does_not_receive_email_but_gets_neutral_response(): void
    {
        Notification::fake();

        $user = User::factory()->create(['is_active' => false]);

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('status');

        Notification::assertNotSentTo($user, ResetPassword::class);
    }

    public function test_unknown_email_gets_neutral_response(): void
    {
        Notification::fake();

        $response = $this->post('/forgot-password', [
            'email' => 'unknown@example.com',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('status');
        
        Notification::assertNothingSent();
    }

    public function test_rate_limiting_is_applied_and_email_is_hashed_in_limiter_key(): void
    {
        Notification::fake();

        // 5 requests allowed per minute for IP + Email combination
        $email = 'test@example.com';
        
        for ($i = 0; $i < 5; $i++) {
            $this->post('/forgot-password', ['email' => $email]);
        }

        $response = $this->post('/forgot-password', ['email' => $email]);

        $response->assertStatus(429); // Too many requests
        
        // Assert rate limiter key does not contain plaintext email
        $hashedEmail = md5($email);
        $this->assertTrue(RateLimiter::tooManyAttempts($hashedEmail . '|' . request()->ip(), 5));
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $response = $this->get('/reset-password/fake-token?email=test@example.com');

        $response->assertStatus(200);
        $response->assertSee('test@example.com');
        $response->assertSee('fake-token');
    }

    public function test_password_can_be_reset_with_valid_token_and_previous_sessions_invalidated(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        
        $token = Password::broker()->createToken($user);
        
        // Let's create an existing session for the user (previous session)
        $this->actingAs($user);
        session()->put('old_session', 'yes');
        
        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('status');
        
        $user->refresh();
        $this->assertTrue(Hash::check('NewPass123!', $user->password));
        
        // Token single use check
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
        
        // After Auth::logoutOtherDevices and login redirects, the user might be logged out and required to login again 
        // since the test explicitly says "önceki oturumların geçersizliği kanıtlansın."
        // We will assert the current session is either invalidated or logged out, depending on implementation. 
        // Our implementation plan logs them out or invalidates other sessions.
        $this->assertNull(session('old_session'));
    }

    public function test_invalid_token_is_rejected(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        
        $response = $this->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertFalse(Hash::check('NewPass123!', $user->password));
    }

    public function test_password_validation_rules_apply(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $token = Password::broker()->createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertSessionHasErrors(['password']);
    }
}
