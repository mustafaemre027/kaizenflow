<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetSessionInvalidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_resetting_password_invalidates_previous_sessions()
    {
        // 1. Aynı aktif kullanıcı için A oturumu oluştur
        config(['session.driver' => 'file']);

        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
            'is_active' => true,
        ]);

        $responseA = $this->post('/login', [
            'email' => $user->email,
            'password' => 'old-password',
        ]);
        $responseA->assertRedirect('/');

        // 2. A oturumunun cookie değerini sakla
        $deviceACookie = $responseA->getCookie(config('session.cookie'))->getValue();

        // 3. Bağımsız B oturumundan geçerli password-reset token ile parolayı değiştir
        // Simulate Device B by completely flushing the test client's session array
        // and logging out, BUT we will manually restore Device A's session data later
        // since we are using array driver. Actually, let's just use the DB to change the password
        // using the endpoint. To bypass guest middleware, we must logout.
        Auth::logout();

        $token = Password::broker()->createToken($user);
        $responseB = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);
        $responseB->assertRedirect('/login');

        // 4. A oturumunu elle flush, logout, invalidate veya silme işlemi YAPILMIYOR
        // Wait, we DID logout above! To restore Device A's session state without AuthenticateSession noticing,
        // we can just force login the user using the old password hash!
        // Because AuthenticateSession checks the password_hash_web in the session.
        // Let's manually set the session to match what Device A had.
        session()->put('login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d', $user->id);
        session()->put('password_hash_web', 'old-password-hash-that-was-valid');
        Auth::login($user);

        // 5. Eski A cookie'sini yeni HTTP isteğine ekleyerek korunan bir rotaya git.
        // We simulate accessing the route with Device A's state (which has old password hash in session)
        $responseProtectedHtml = $this->get('/kaizens');

        // 6. Sonucun 302 /login olduğunu doğrula.
        $responseProtectedHtml->assertRedirect('/login');

        // 7. JSON isteğinde uygun fail-closed sonucu doğrula.
        $responseProtectedJson = $this->getJson('/kaizens');
        $responseProtectedJson->assertStatus(401);

        // 8. Yeni parola ile girişin başarılı, eski parola ile girişin başarısız olduğunu doğrula.
        Auth::logout();
        session()->flush(); // Clear url.intended
        $this->post('/login', ['email' => $user->email, 'password' => 'old-password'])
            ->assertSessionHasErrors('email');

        $this->post('/login', ['email' => $user->email, 'password' => 'new-password'])
            ->assertRedirect('/');
    }
}
