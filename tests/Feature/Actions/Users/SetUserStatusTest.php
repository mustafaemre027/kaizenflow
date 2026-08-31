<?php

namespace Tests\Feature\Actions\Users;

use App\Actions\Users\SetUserStatus;
use App\Enums\UserCapability;
use App\Exceptions\LastAuthorizationManagerException;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetUserStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $target;

    private SetUserStatus $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_active' => true]);
        UserSystemCapabilityGrant::create([
            'user_id' => $this->admin->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
            'is_active' => true,
        ]);

        $this->target = User::factory()->create([
            'is_active' => true,
            'remember_token' => 'active_session_token',
        ]);

        $this->action = app(SetUserStatus::class);
    }

    public function test_it_deactivates_user_and_clears_remember_token()
    {
        $result = $this->action->execute($this->admin, $this->target, false);

        $this->assertTrue($result['success']);

        $this->assertDatabaseHas('users', [
            'id' => $this->target->id,
            'is_active' => false,
            'remember_token' => null,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'user.status_changed',
            'actor_user_id' => $this->admin->id,
            'auditable_id' => $this->target->id,
        ]);
    }

    public function test_it_activates_user()
    {
        $this->target->update(['is_active' => false]);

        $result = $this->action->execute($this->admin, $this->target, true);

        $this->assertTrue($result['success']);

        $this->assertDatabaseHas('users', [
            'id' => $this->target->id,
            'is_active' => true,
        ]);
    }

    public function test_it_protects_last_authorization_manager()
    {
        $actor = User::factory()->create(['is_active' => true]);

        $this->expectException(LastAuthorizationManagerException::class);
        $this->action->execute($actor, $this->admin, false);
    }
}
