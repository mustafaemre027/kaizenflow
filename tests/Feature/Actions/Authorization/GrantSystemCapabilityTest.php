<?php

namespace Tests\Feature\Actions\Authorization;

use App\Actions\Authorization\GrantSystemCapability;
use App\Enums\UserCapability;
use App\Exceptions\ScopeMismatchException;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use App\Services\AppendAuditLog;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GrantSystemCapabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_throws_scope_mismatch_for_department_capability(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        $action = app(GrantSystemCapability::class);

        $this->expectException(ScopeMismatchException::class);
        $action->execute($actor, $target, UserCapability::KAIZEN_IMPLEMENTATION_START);
    }

    public function test_actor_must_be_active(): void
    {
        $actor = User::factory()->create(['is_active' => false]);
        $target = User::factory()->create();
        $action = app(GrantSystemCapability::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unauthorized action.');

        $action->execute($actor, $target, UserCapability::ORGANIZATION_VIEW);
    }

    public function test_target_must_be_active(): void
    {
        $actor = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create([
            'user_id' => $actor->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
        ]);
        UserSystemCapabilityGrant::factory()->create([
            'user_id' => $actor->id,
            'capability' => UserCapability::ORGANIZATION_VIEW,
        ]);

        $target = User::factory()->create(['is_active' => false]);
        $action = app(GrantSystemCapability::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unauthorized action.');

        $action->execute($actor, $target, UserCapability::ORGANIZATION_VIEW);
    }

    public function test_actor_cannot_grant_to_self(): void
    {
        $actor = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create([
            'user_id' => $actor->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
        ]);

        $action = app(GrantSystemCapability::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unauthorized action.');

        $action->execute($actor, $actor, UserCapability::ORGANIZATION_VIEW);
    }

    public function test_actor_must_have_authorization_manage_and_the_capability_itself(): void
    {
        $actor = User::factory()->create();
        // Has AUTHORIZATION_MANAGE but not ORGANIZATION_VIEW
        UserSystemCapabilityGrant::factory()->create([
            'user_id' => $actor->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
        ]);

        $target = User::factory()->create();
        $action = app(GrantSystemCapability::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unauthorized action.');
        $action->execute($actor, $target, UserCapability::ORGANIZATION_VIEW);
    }

    public function test_it_creates_new_grant_and_audit_log_when_no_previous_record_exists(): void
    {
        $actor = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create(['user_id' => $actor->id, 'capability' => UserCapability::AUTHORIZATION_MANAGE]);
        UserSystemCapabilityGrant::factory()->create(['user_id' => $actor->id, 'capability' => UserCapability::ORGANIZATION_VIEW]);

        $target = User::factory()->create();

        $action = app(GrantSystemCapability::class);
        $action->execute($actor, $target, UserCapability::ORGANIZATION_VIEW);

        $this->assertDatabaseHas('user_system_capability_grants', [
            'user_id' => $target->id,
            'capability' => UserCapability::ORGANIZATION_VIEW->value,
            'is_active' => true,
            'granted_by_user_id' => $actor->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $actor->id,
            'event' => 'authorization.system_capability.granted',
        ]);

        $log = AuditLog::first();
        $this->assertEquals($actor->id, $log->metadata['actor_user_id']);
        $this->assertEquals($target->id, $log->metadata['target_user_id']);
        $this->assertEquals(UserCapability::ORGANIZATION_VIEW->value, $log->metadata['capability']);
        $this->assertEquals('system', $log->metadata['scope']);
        $this->assertNull($log->metadata['old_is_active']);
        $this->assertTrue($log->metadata['new_is_active']);
    }

    public function test_it_reactivates_inactive_grant(): void
    {
        $actor = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create(['user_id' => $actor->id, 'capability' => UserCapability::AUTHORIZATION_MANAGE]);
        UserSystemCapabilityGrant::factory()->create(['user_id' => $actor->id, 'capability' => UserCapability::ORGANIZATION_VIEW]);

        $target = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create([
            'user_id' => $target->id,
            'capability' => UserCapability::ORGANIZATION_VIEW,
            'is_active' => false,
            'granted_by_user_id' => $target->id, // old granter
        ]);

        $action = app(GrantSystemCapability::class);
        $action->execute($actor, $target, UserCapability::ORGANIZATION_VIEW);

        $this->assertDatabaseHas('user_system_capability_grants', [
            'user_id' => $target->id,
            'capability' => UserCapability::ORGANIZATION_VIEW->value,
            'is_active' => true,
            'granted_by_user_id' => $actor->id,
        ]);

        $log = AuditLog::first();
        $this->assertEquals($actor->id, $log->metadata['actor_user_id']);
        $this->assertEquals($target->id, $log->metadata['target_user_id']);
        $this->assertEquals(UserCapability::ORGANIZATION_VIEW->value, $log->metadata['capability']);
        $this->assertEquals('system', $log->metadata['scope']);
        $this->assertFalse($log->metadata['old_is_active']);
        $this->assertTrue($log->metadata['new_is_active']);
    }

    public function test_it_is_idempotent_no_op_when_already_active(): void
    {
        $actor = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create(['user_id' => $actor->id, 'capability' => UserCapability::AUTHORIZATION_MANAGE]);
        UserSystemCapabilityGrant::factory()->create(['user_id' => $actor->id, 'capability' => UserCapability::ORGANIZATION_VIEW]);

        $target = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create([
            'user_id' => $target->id,
            'capability' => UserCapability::ORGANIZATION_VIEW,
            'is_active' => true,
            'granted_by_user_id' => $target->id, // keep old granter
        ]);

        $action = app(GrantSystemCapability::class);
        $action->execute($actor, $target, UserCapability::ORGANIZATION_VIEW);

        $this->assertDatabaseHas('user_system_capability_grants', [
            'user_id' => $target->id,
            'capability' => UserCapability::ORGANIZATION_VIEW->value,
            'is_active' => true,
            'granted_by_user_id' => $target->id, // not changed
        ]);

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_it_rolls_back_grant_if_audit_fails(): void
    {
        $actor = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create(['user_id' => $actor->id, 'capability' => UserCapability::AUTHORIZATION_MANAGE]);
        UserSystemCapabilityGrant::factory()->create(['user_id' => $actor->id, 'capability' => UserCapability::ORGANIZATION_VIEW]);

        $target = User::factory()->create();

        $mockAudit = $this->createStub(AppendAuditLog::class);
        $mockAudit->method('execute')->willThrowException(new Exception('Audit failed'));
        $this->app->instance(AppendAuditLog::class, $mockAudit);

        $action = app(GrantSystemCapability::class);

        try {
            $action->execute($actor, $target, UserCapability::ORGANIZATION_VIEW);
        } catch (Exception $e) {
            $this->assertEquals('Audit failed', $e->getMessage());
        }

        $this->assertDatabaseMissing('user_system_capability_grants', [
            'user_id' => $target->id,
            'capability' => UserCapability::ORGANIZATION_VIEW->value,
        ]);
    }

    public function test_authorization_is_validated_inside_transaction(): void
    {
        $actor = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create(['user_id' => $actor->id, 'capability' => UserCapability::AUTHORIZATION_MANAGE]);
        UserSystemCapabilityGrant::factory()->create(['user_id' => $actor->id, 'capability' => UserCapability::ORGANIZATION_VIEW]);
        $target = User::factory()->create();

        $action = app(GrantSystemCapability::class);

        $checked = false;
        DB::listen(function ($query) use (&$checked, $actor) {
            $sql = strtolower($query->sql);
            if (str_contains($sql, 'user_system_capability_grants') && str_contains($sql, 'select') && in_array($actor->id, $query->bindings)) {
                $this->assertGreaterThan(1, DB::transactionLevel(), 'Authorization check for actor must happen inside a transaction.');
                $checked = true;
            }
        });

        $action->execute($actor, $target, UserCapability::ORGANIZATION_VIEW);
        $this->assertTrue($checked);
    }

    public function test_stale_actor_is_rejected(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        UserSystemCapabilityGrant::factory()->create(['user_id' => $actor->id, 'capability' => UserCapability::AUTHORIZATION_MANAGE]);
        UserSystemCapabilityGrant::factory()->create(['user_id' => $actor->id, 'capability' => UserCapability::ORGANIZATION_VIEW]);
        $target = User::factory()->create();

        User::where('id', $actor->id)->update(['is_active' => false]);

        $action = app(GrantSystemCapability::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unauthorized action.');

        $action->execute($actor, $target, UserCapability::ORGANIZATION_VIEW);
    }

    public function test_stale_target_is_rejected(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        UserSystemCapabilityGrant::factory()->create(['user_id' => $actor->id, 'capability' => UserCapability::AUTHORIZATION_MANAGE]);
        UserSystemCapabilityGrant::factory()->create(['user_id' => $actor->id, 'capability' => UserCapability::ORGANIZATION_VIEW]);
        $target = User::factory()->create(['is_active' => true]);

        User::where('id', $target->id)->update(['is_active' => false]);

        $action = app(GrantSystemCapability::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unauthorized action.');

        $action->execute($actor, $target, UserCapability::ORGANIZATION_VIEW);
    }

    public function test_stale_authorization_grant_is_rejected(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        UserSystemCapabilityGrant::factory()->create(['user_id' => $actor->id, 'capability' => UserCapability::AUTHORIZATION_MANAGE]);
        UserSystemCapabilityGrant::factory()->create(['user_id' => $actor->id, 'capability' => UserCapability::ORGANIZATION_VIEW]);
        $target = User::factory()->create();

        UserSystemCapabilityGrant::where('user_id', $actor->id)->where('capability', UserCapability::AUTHORIZATION_MANAGE->value)->update(['is_active' => false]);

        $action = app(GrantSystemCapability::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unauthorized action.');

        $action->execute($actor, $target, UserCapability::ORGANIZATION_VIEW);
    }

    public function test_stale_exact_capability_is_rejected(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        UserSystemCapabilityGrant::factory()->create(['user_id' => $actor->id, 'capability' => UserCapability::AUTHORIZATION_MANAGE]);
        UserSystemCapabilityGrant::factory()->create(['user_id' => $actor->id, 'capability' => UserCapability::ORGANIZATION_VIEW]);
        $target = User::factory()->create();

        UserSystemCapabilityGrant::where('user_id', $actor->id)->where('capability', UserCapability::ORGANIZATION_VIEW->value)->update(['is_active' => false]);

        $action = app(GrantSystemCapability::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unauthorized action.');

        $action->execute($actor, $target, UserCapability::ORGANIZATION_VIEW);
    }

    public function test_unauthorized_actor_cannot_perform_no_op(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        UserSystemCapabilityGrant::factory()->create(['user_id' => $actor->id, 'capability' => UserCapability::AUTHORIZATION_MANAGE]);
        UserSystemCapabilityGrant::factory()->create(['user_id' => $actor->id, 'capability' => UserCapability::ORGANIZATION_VIEW]);
        $target = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create(['user_id' => $target->id, 'capability' => UserCapability::ORGANIZATION_VIEW, 'is_active' => true]);

        UserSystemCapabilityGrant::where('user_id', $actor->id)->where('capability', UserCapability::AUTHORIZATION_MANAGE->value)->update(['is_active' => false]);

        $action = app(GrantSystemCapability::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unauthorized action.');

        $action->execute($actor, $target, UserCapability::ORGANIZATION_VIEW);
    }
}
