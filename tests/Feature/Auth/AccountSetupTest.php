<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AccountSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_pending_user_can_setup_password_and_clears_must_set_password(): void
    {
        Event::fake();

        $user = User::factory()->create([
            'is_active' => true,
            'must_set_password' => true,
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/login');

        $user->refresh();

        $this->assertFalse($user->must_set_password);
        $this->assertTrue(Hash::check('new-password', $user->password));

        Event::assertDispatched(PasswordReset::class, function ($e) use ($user) {
            return $e->user->id === $user->id;
        });
    }

    public function test_inactive_user_cannot_reset_password_even_with_valid_token(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
            'must_set_password' => true,
        ]);

        $token = Password::broker()->createToken($user);
        $oldPasswordHash = $user->password;

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertSessionHasErrors(['email' => __('passwords.user')]);

        $user->refresh();

        $this->assertTrue($user->must_set_password);
        $this->assertEquals($oldPasswordHash, $user->password);
    }

    public function test_token_cannot_be_reused(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'must_set_password' => true,
        ]);

        $token = Password::broker()->createToken($user);

        // 1st use
        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $user->refresh();
        $this->assertFalse($user->must_set_password);

        // 2nd use
        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'another-password',
            'password_confirmation' => 'another-password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $user->refresh();

        $this->assertTrue(Hash::check('new-password', $user->password));
    }
}
