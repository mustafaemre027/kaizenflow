<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetSessionInvalidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_resetting_password_invalidates_previous_sessions()
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
            'is_active' => true,
        ]);

        // Step 1: Login to create an active session
        $response1 = $this->post('/login', [
            'email' => $user->email,
            'password' => 'old-password',
        ]);
        $response1->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        
        // Save the current session data (cookie) to simulate another browser
        $sessionCookie = $response1->getCookie(config('session.cookie'));

        // Flush test client session to simulate a different device/guest
        \Illuminate\Support\Facades\Auth::logout();
        $this->flushSession();
        $this->assertGuest();

        // Step 2: Request password reset from the new device
        $token = Password::broker()->createToken($user);
        $response2 = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response2->assertRedirect('/login');
        
        // Step 3: Try to use the old session cookie from device 1
        // We set the cookie manually on the request
        $response3 = $this->withUnencryptedCookies([
            config('session.cookie') => $sessionCookie->getValue()
        ])->get('/kaizens');

        // It should redirect to login because AuthenticateSession invalidated it
        $response3->assertRedirect('/login');
        
        // And the user should be a guest now
        $this->assertGuest();
    }
}
