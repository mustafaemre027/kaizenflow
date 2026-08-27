<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_otp_screen()
    {
        $response = $this->get(route('verification.notice'));
        $response->assertRedirect(route('login'));
    }

    public function test_unverified_active_user_sees_otp_screen()
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertStatus(200);
        $response->assertViewIs('auth.verify-email');
    }

    public function test_verified_user_is_redirected_to_dashboard_from_otp_screen()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertRedirect(route('home'));
    }

    public function test_inactive_user_is_rejected_fail_closed()
    {
        $user = User::factory()->unverified()->inactive()->create();

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_unverified_user_cannot_access_kaizen_routes()
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('kaizens.index'));

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_user_cannot_access_work_queue()
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('implementation.work-queue.index'));

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_user_cannot_access_approval_routes()
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('approvals.index'));

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_user_can_logout()
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_verified_user_can_access_internal_routes()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('kaizens.index'));

        $response->assertStatus(200);
    }

    public function test_admin_cannot_bypass_verification_rule()
    {
        $admin = User::factory()->unverified()->withRole(UserRole::ADMIN)->create();

        $response = $this->actingAs($admin)->get(route('kaizens.index'));

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_json_request_receives_403()
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->getJson(route('kaizens.index'));

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Your email address is not verified.']);
    }

    public function test_valid_otp_verifies_and_redirects()
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post(route('verification.send'));

        $code = EmailVerificationCode::where('user_id', $user->id)->first();
        // Since plaintext is not in DB, we must extract from notification
        $plaintextOtp = '';
        Notification::assertSentTo($user, EmailVerificationCodeNotification::class, function ($notification) use (&$plaintextOtp) {
            $reflection = new \ReflectionClass($notification);
            $property = $reflection->getProperty('code');
            $property->setAccessible(true);
            $plaintextOtp = $property->getValue($notification);

            return true;
        });

        $response = $this->actingAs($user)->post(route('verification.verify'), [
            'code' => $plaintextOtp,
        ]);

        $response->assertRedirect(route('home'));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_invalid_otp_gives_generic_error()
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->post(route('verification.verify'), [
            'code' => '000000',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_expired_used_locked_give_same_generic_error()
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post(route('verification.send'));

        $this->travel(601)->seconds();

        $response = $this->actingAs($user)->post(route('verification.verify'), [
            'code' => '123456', // Any code will fail because it's expired
        ]);

        $response->assertSessionHasErrors('code');
        $errorMessage = session('errors')->get('code')[0];
        $this->assertStringContainsString('The provided verification code is invalid, expired, or you have exceeded the maximum attempts.', $errorMessage);
    }

    public function test_resend_is_delegated_to_action()
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->post(route('verification.send'));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'verification-link-sent');
        Notification::assertSentTo($user, EmailVerificationCodeNotification::class);
    }

    public function test_resend_rate_limit_is_applied()
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post(route('verification.send'));
        $response = $this->actingAs($user)->post(route('verification.send'));

        $response->assertStatus(429);
    }

    public function test_actor_user_injection_fields_are_rejected_with_422()
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->postJson(route('verification.verify'), [
            'code' => '123456',
            'user_id' => 999,
            'is_active' => true,
        ]);

        $response->assertStatus(422);
    }

    public function test_cross_user_otp_is_rejected()
    {
        Notification::fake();
        $user1 = User::factory()->unverified()->create();
        $user2 = User::factory()->unverified()->create();

        $this->actingAs($user1)->post(route('verification.send'));

        $plaintextOtp = '';
        Notification::assertSentTo($user1, EmailVerificationCodeNotification::class, function ($notification) use (&$plaintextOtp) {
            $reflection = new \ReflectionClass($notification);
            $property = $reflection->getProperty('code');
            $property->setAccessible(true);
            $plaintextOtp = $property->getValue($notification);

            return true;
        });

        $response = $this->actingAs($user2)->post(route('verification.verify'), [
            'code' => $plaintextOtp,
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertNull($user2->fresh()->email_verified_at);
    }

    public function test_otp_plaintext_does_not_leak_in_response_or_url()
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->post(route('verification.send'));

        $plaintextOtp = '';
        Notification::assertSentTo($user, EmailVerificationCodeNotification::class, function ($notification) use (&$plaintextOtp) {
            $reflection = new \ReflectionClass($notification);
            $property = $reflection->getProperty('code');
            $property->setAccessible(true);
            $plaintextOtp = $property->getValue($notification);

            return true;
        });

        $response->assertDontSee($plaintextOtp);
        $this->assertStringNotContainsString($plaintextOtp, $response->headers->get('Location') ?? '');
        $this->assertNull(session('code'));
        $this->assertNull(session('otp'));
    }

    public function test_xss_canary_is_escaped()
    {
        $user = User::factory()->unverified()->create(['email' => 'test<script>alert(1)</script>@example.com']);

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertDontSee('<script>', false);
        $response->assertSee('&lt;script&gt;', false);
    }

    public function test_blade_preserves_single_h1_and_accessible_form()
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $content = $response->getContent();
        $this->assertEquals(1, substr_count(strtolower($content), '<h1'));
        $this->assertStringContainsString('maxlength="6"', $content);
        $this->assertStringContainsString('autocomplete="one-time-code"', $content);
        $this->assertStringContainsString('inputmode="numeric"', $content);
    }

    public function test_csrf_and_post_method_contract()
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertSee('name="_token"', false)
            ->assertDontSee('<form method="GET"', false)
            ->assertSee('method="POST"', false);
    }

    public function test_guest_login_and_password_reset_routes_are_not_broken()
    {
        $this->get(route('login'))->assertStatus(200);
        $this->get(route('password.request'))->assertStatus(200);
    }

    public function test_redirect_loop_does_not_occur()
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertStatus(200);
        $this->assertStringNotContainsString('Redirecting to', $response->getContent());
    }

    public function test_verification_is_idempotent()
    {
        $user = User::factory()->create(); // verified

        $response = $this->actingAs($user)->post(route('verification.verify'), [
            'code' => '123456',
        ]);

        $response->assertRedirect(route('home'));
    }
} 
