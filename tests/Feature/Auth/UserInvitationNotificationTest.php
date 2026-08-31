<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\CustomResetPasswordNotification;
use App\Notifications\UserInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserInvitationNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_password_reset_routes_to_invitation_if_must_set_password(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'must_set_password' => true,
        ]);

        $user->sendPasswordResetNotification('fake-token');

        Notification::assertSentTo(
            [$user], UserInvitationNotification::class
        );

        Notification::assertNotSentTo(
            [$user], CustomResetPasswordNotification::class
        );
    }

    public function test_send_password_reset_routes_to_custom_reset_if_not_must_set_password(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'must_set_password' => false,
        ]);

        $user->sendPasswordResetNotification('fake-token');

        Notification::assertSentTo(
            [$user], CustomResetPasswordNotification::class
        );

        Notification::assertNotSentTo(
            [$user], UserInvitationNotification::class
        );
    }
}
