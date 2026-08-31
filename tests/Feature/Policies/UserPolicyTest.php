<?php

namespace Tests\Feature\Policies;

use App\Enums\UserCapability;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $target;

    protected function setUp(): void
    {
        parent::setUp();
        $this->target = User::factory()->create(['is_active' => true]);
    }

    public function test_view_capabilities_requires_active_actor()
    {
        $actor = User::factory()->create(['is_active' => false]);
        $this->assertFalse($actor->can('viewCapabilities', $this->target));
    }

    public function test_view_capabilities_requires_authorization_manage_and_organization_view()
    {
        $actor = User::factory()->create(['is_active' => true]);

        // Neither
        $this->assertFalse($actor->can('viewCapabilities', $this->target));

        // Only Auth Manage
        UserSystemCapabilityGrant::create(['user_id' => $actor->id, 'capability' => UserCapability::AUTHORIZATION_MANAGE, 'is_active' => true]);
        $this->assertFalse($actor->can('viewCapabilities', $this->target));

        // Both
        UserSystemCapabilityGrant::create(['user_id' => $actor->id, 'capability' => UserCapability::ORGANIZATION_VIEW, 'is_active' => true]);
        $this->assertTrue($actor->can('viewCapabilities', $this->target));
    }

    public function test_view_capabilities_allows_self()
    {
        $actor = clone $this->target;
        UserSystemCapabilityGrant::create(['user_id' => $actor->id, 'capability' => UserCapability::AUTHORIZATION_MANAGE, 'is_active' => true]);
        UserSystemCapabilityGrant::create(['user_id' => $actor->id, 'capability' => UserCapability::ORGANIZATION_VIEW, 'is_active' => true]);

        $this->assertTrue($actor->can('viewCapabilities', $actor));
    }

    public function test_manage_capabilities_denies_self()
    {
        $actor = User::factory()->create(['is_active' => true]);
        UserSystemCapabilityGrant::create(['user_id' => $actor->id, 'capability' => UserCapability::AUTHORIZATION_MANAGE, 'is_active' => true]);
        UserSystemCapabilityGrant::create(['user_id' => $actor->id, 'capability' => UserCapability::ORGANIZATION_VIEW, 'is_active' => true]);

        $this->assertTrue($actor->can('viewCapabilities', $actor)); // View is allowed
        $this->assertFalse($actor->can('manageCapabilities', $actor)); // Manage is denied
    }
}
