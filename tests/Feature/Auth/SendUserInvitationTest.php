<?php

namespace Tests\Feature\Auth;

use App\Actions\Users\SendUserInvitation;
use App\Enums\UserCapability;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class SendUserInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_authorized_active_actor_can_send_invitation_to_active_pending_target(): void
    {
        $actor = User::factory()->create(['is_active' => true]);

        UserSystemCapabilityGrant::create([
            'user_id' => $actor->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
            'is_active' => true,
        ]);

        $target = User::factory()->create([
            'is_active' => true,
            'must_set_password' => true,
            'invitation_sent_at' => null,
        ]);

        $action = app(SendUserInvitation::class);
        $status = $action->execute($actor, $target);

        $this->assertEquals(Password::RESET_LINK_SENT, $status);

        $target->refresh();
        $this->assertNotNull($target->invitation_sent_at);

        $audit = AuditLog::where('event', 'user.invitation_sent')->first();
        $this->assertNotNull($audit);
        $this->assertEquals($actor->id, $audit->actor_user_id);
        $this->assertEquals($target->id, $audit->auditable_id);
    }

    public function test_actor_without_authorization_cannot_send(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        $target = User::factory()->create([
            'is_active' => true,
            'must_set_password' => true,
        ]);

        $action = app(SendUserInvitation::class);

        $this->expectException(DomainException::class);
        $action->execute($actor, $target);
    }

    public function test_inactive_actor_cannot_send(): void
    {
        $actor = User::factory()->create(['is_active' => false]);

        UserSystemCapabilityGrant::create([
            'user_id' => $actor->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
            'is_active' => true,
        ]);

        $target = User::factory()->create([
            'is_active' => true,
            'must_set_password' => true,
        ]);

        $action = app(SendUserInvitation::class);

        $this->expectException(DomainException::class);
        $action->execute($actor, $target);
    }

    public function test_cannot_send_to_inactive_target(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        UserSystemCapabilityGrant::create([
            'user_id' => $actor->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
            'is_active' => true,
        ]);

        $target = User::factory()->create([
            'is_active' => false,
            'must_set_password' => true,
        ]);

        $action = app(SendUserInvitation::class);

        $this->expectException(DomainException::class);
        $action->execute($actor, $target);
    }

    public function test_cannot_send_to_ready_target(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        UserSystemCapabilityGrant::create([
            'user_id' => $actor->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
            'is_active' => true,
        ]);

        $target = User::factory()->create([
            'is_active' => true,
            'must_set_password' => false,
        ]);

        $action = app(SendUserInvitation::class);

        $this->expectException(DomainException::class);
        $action->execute($actor, $target);
    }

    public function test_cannot_send_to_self(): void
    {
        $actor = User::factory()->create([
            'is_active' => true,
            'must_set_password' => true,
        ]);
        UserSystemCapabilityGrant::create([
            'user_id' => $actor->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
            'is_active' => true,
        ]);

        $action = app(SendUserInvitation::class);

        $this->expectException(DomainException::class);
        $action->execute($actor, $actor);
    }
}
