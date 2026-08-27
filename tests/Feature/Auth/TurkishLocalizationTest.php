<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\CustomResetPasswordNotification;
use App\Notifications\EmailVerificationCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class TurkishLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_email_validation_message()
    {
        $response = $this->post(route('password.email'), [
            'email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertEquals(
            'Lütfen geçerli bir e-posta adresi girin.',
            session('errors')->first('email')
        );
    }

    public function test_neutral_password_reset_message()
    {
        $response = $this->from('/forgot-password')->post(route('password.email'), [
            'email' => 'unknown@example.com',
        ]);

        $response->assertRedirect('/forgot-password');
        $response->assertSessionHas('status', 'Girdiğiniz e-posta adresi bir hesapla eşleşiyorsa şifre sıfırlama bağlantısı kısa süre içinde gönderilecektir. Gelen kutunuzu ve gereksiz e-posta klasörünü kontrol edin.');
    }

    public function test_successful_password_reset_message()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('oldpassword'),
            'is_active' => true,
        ]);
        $token = Password::broker()->createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'Şifreniz başarıyla değiştirildi. Yeni şifrenizle giriş yapabilirsiniz.');
    }

    public function test_invalid_token_message()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $response = $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertEquals(
            'Bu şifre sıfırlama bağlantısı geçersiz veya süresi dolmuş. Lütfen yeni bir bağlantı talep edin.',
            session('errors')->first('email')
        );
    }

    public function test_invalid_login_message()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertEquals(
            'E-posta adresi veya parola hatalı.',
            session('errors')->first('email')
        );
    }

    public function test_rate_limit_message()
    {
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post(route('login.store'), [
                'email' => 'throttle@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Çok fazla deneme yaptınız. Lütfen ',
            session('errors')->first('email')
        );
        $this->assertStringContainsString(
            ' saniye sonra tekrar deneyin.',
            session('errors')->first('email')
        );
    }

    public function test_password_reset_email_content()
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $this->post(route('password.email'), [
            'email' => 'test@example.com',
        ]);

        Notification::assertSentTo(
            $user,
            CustomResetPasswordNotification::class,
            function (CustomResetPasswordNotification $notification) use ($user) {
                if (method_exists($notification, 'toMail')) {
                    $mailData = $notification->toMail($user);

                    return $mailData->subject === 'KaizenFlow Şifre Sıfırlama'
                        && $mailData->greeting === 'Merhaba,'
                        && $mailData->actionText === 'Şifremi Sıfırla'
                        && in_array('Hesabınız için bir şifre sıfırlama talebi aldık.', $mailData->introLines)
                        && in_array('Bu bağlantı 60 dakika boyunca geçerlidir.', $mailData->outroLines)
                        && in_array('Bu talebi siz oluşturmadıysanız herhangi bir işlem yapmanız gerekmez.', $mailData->outroLines);
                }

                return false;
            }
        );
    }

    public function test_password_reset_email_rendered_content()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $notification = new CustomResetPasswordNotification('dummy-token');
        $mail = $notification->toMail($user);

        // Render HTML
        $html = $mail->render();
        $this->assertStringNotContainsString('Laravel', $html);
        $this->assertStringNotContainsString(':actionText', $html);
        $this->assertStringNotContainsString('All rights reserved', $html);
        $this->assertStringContainsString('KaizenFlow', $html);
        $this->assertStringContainsString('Şifremi Sıfırla', $html);
        $this->assertStringContainsString('butonuna tıklamakta sorun yaşıyorsanız', $html);
        $this->assertStringContainsString('Tüm hakları saklıdır', $html);
    }

    public function test_otp_email_content()
    {
        $user = User::factory()->unverified()->create([
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $notification = new EmailVerificationCodeNotification('123456');
        $mailData = $notification->toMail($user);

        $this->assertEquals('KaizenFlow E-posta Doğrulama Kodu', $mailData->subject);
        $this->assertEquals('Merhaba,', $mailData->greeting);
        $this->assertStringContainsString('123456', $mailData->introLines[0]);
        $this->assertContains('Bu kod 10 dakika geçerlidir.', $mailData->introLines);
        $this->assertContains('Lütfen bu kodu kimseyle paylaşmayın.', $mailData->introLines);
    }
}
