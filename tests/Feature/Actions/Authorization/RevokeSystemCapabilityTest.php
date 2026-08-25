<?php

namespace Tests\Feature\Actions\Authorization;

use App\Actions\Authorization\RevokeSystemCapability;
use App\Enums\UserCapability;
use App\Exceptions\LastAuthorizationManagerException;
use App\Exceptions\ScopeMismatchException;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use App\Services\AppendAuditLog;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RevokeSystemCapabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_throws_scope_mismatch_for_department_capability(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        $action = app(RevokeSystemCapability::class);

        $this->expectException(ScopeMismatchException::class);
        $action->execute($actor, $target, UserCapability::KAIZEN_IMPLEMENTATION_START);
    }

    public function test_actor_must_be_active(): void
    {
        $actor = User::factory()->create(['is_active' => false]);
        $target = User::factory()->create();
        $action = app(RevokeSystemCapability::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unauthorized action.');

        $action->execute($actor, $target, UserCapability::ORGANIZATION_VIEW);
    }

    public function test_actor_must_have_authorization_manage(): void
    {
        $actor = User::factory()->create();
        // Doesn't have AUTHORIZATION_MANAGE
        $target = User::factory()->create();
        $action = app(RevokeSystemCapability::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unauthorized action.');
        $action->execute($actor, $target, UserCapability::ORGANIZATION_VIEW);
    }

    public function test_it_is_idempotent_no_op_when_grant_does_not_exist(): void
    {
        $actor = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create([
            'user_id' => $actor->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
        ]);

        $target = User::factory()->create();

        $action = app(RevokeSystemCapability::class);
        $action->execute($actor, $target, UserCapability::ORGANIZATION_VIEW);

        $this->assertDatabaseMissing('user_system_capability_grants', [
            'user_id' => $target->id,
            'capability' => UserCapability::ORGANIZATION_VIEW->value,
        ]);

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_it_is_idempotent_no_op_when_grant_is_already_inactive(): void
    {
        $actor = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create([
            'user_id' => $actor->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
        ]);

        $target = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create([
            'user_id' => $target->id,
            'capability' => UserCapability::ORGANIZATION_VIEW,
            'is_active' => false,
        ]);

        $action = app(RevokeSystemCapability::class);
        $action->execute($actor, $target, UserCapability::ORGANIZATION_VIEW);

        $this->assertDatabaseHas('user_system_capability_grants', [
            'user_id' => $target->id,
            'capability' => UserCapability::ORGANIZATION_VIEW->value,
            'is_active' => false,
        ]);

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_it_revokes_grant_and_creates_audit_log(): void
    {
        $actor = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create([
            'user_id' => $actor->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
        ]);

        $target = User::factory()->create();
        $grant = UserSystemCapabilityGrant::factory()->create([
            'user_id' => $target->id,
            'capability' => UserCapability::ORGANIZATION_VIEW,
            'is_active' => true,
            'granted_by_user_id' => $target->id,
        ]);

        $action = app(RevokeSystemCapability::class);
        $action->execute($actor, $target, UserCapability::ORGANIZATION_VIEW);

        $this->assertDatabaseHas('user_system_capability_grants', [
            'user_id' => $target->id,
            'capability' => UserCapability::ORGANIZATION_VIEW->value,
            'is_active' => false,
            'granted_by_user_id' => $target->id, // shouldn't change
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $actor->id,
            'event' => 'authorization.system_capability.revoked',
        ]);

        $log = AuditLog::first();
        $this->assertEquals($actor->id, $log->metadata['actor_user_id']);
        $this->assertEquals($target->id, $log->metadata['target_user_id']);
        $this->assertEquals(UserCapability::ORGANIZATION_VIEW->value, $log->metadata['capability']);
        $this->assertEquals('system', $log->metadata['scope']);
        $this->assertTrue($log->metadata['old_is_active']);
        $this->assertFalse($log->metadata['new_is_active']);
    }

    public function test_it_prevents_revoking_last_active_authorization_manager(): void
    {
        // Only one active manager in the system
        $actor = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create([
            'user_id' => $actor->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
            'is_active' => true,
        ]);

        // Has another manager but user is inactive
        $inactiveTarget = User::factory()->create(['is_active' => false]);
        UserSystemCapabilityGrant::factory()->create([
            'user_id' => $inactiveTarget->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
            'is_active' => true,
        ]);

        // Has another active user but grant is inactive
        $target2 = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create([
            'user_id' => $target2->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
            'is_active' => false,
        ]);

        $action = app(RevokeSystemCapability::class);

        // Should use specific Exception
        $this->expectException(LastAuthorizationManagerException::class);
        $action->execute($actor, $actor, UserCapability::AUTHORIZATION_MANAGE);
    }

    public function test_it_allows_revoking_if_there_is_another_active_manager(): void
    {
        $actor = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create([
            'user_id' => $actor->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
            'is_active' => true,
        ]);

        $target = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create([
            'user_id' => $target->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
            'is_active' => true,
        ]);

        $action = app(RevokeSystemCapability::class);
        // Actor revokes their own manager role, since target also has it, this should pass.
        $action->execute($actor, $actor, UserCapability::AUTHORIZATION_MANAGE);

        $this->assertDatabaseHas('user_system_capability_grants', [
            'user_id' => $actor->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE->value,
            'is_active' => false,
        ]);
    }

    public function test_it_locks_manager_count_query_to_prevent_concurrent_revoke_invariant_bypass(): void
    {
        $actor = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create([
            'user_id' => $actor->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
            'is_active' => true,
        ]);

        $target = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create([
            'user_id' => $target->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
            'is_active' => true,
        ]);

        DB::enableQueryLog();

        $action = app(RevokeSystemCapability::class);
        $action->execute($actor, $actor, UserCapability::AUTHORIZATION_MANAGE);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('SQLite does not support FOR UPDATE syntax.');
        }

        $foundLockingCountQuery = false;
        foreach ($queries as $query) {
            $sql = strtolower($query['query']);
            if (str_contains($sql, 'select count(*)') && str_contains($sql, 'for update')) {
                $foundLockingCountQuery = true;
                break;
            }
        }

        $this->assertTrue($foundLockingCountQuery, 'The active manager count query must use lockForUpdate() to prevent concurrent transaction invariant bypass under repeatable read isolation.');
    }

    public function test_it_rolls_back_revoke_if_audit_fails(): void
    {
        $actor = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create([
            'user_id' => $actor->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
        ]);

        $target = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create([
            'user_id' => $target->id,
            'capability' => UserCapability::ORGANIZATION_VIEW,
            'is_active' => true,
        ]);

        $mockAudit = $this->createMock(AppendAuditLog::class);
        $mockAudit->method('execute')->willThrowException(new Exception('Audit failed'));
        $this->app->instance(AppendAuditLog::class, $mockAudit);

        $action = app(RevokeSystemCapability::class);

        try {
            $action->execute($actor, $target, UserCapability::ORGANIZATION_VIEW);
        } catch (Exception $e) {
            $this->assertEquals('Audit failed', $e->getMessage());
        }

        // Grant should remain active
        $this->assertDatabaseHas('user_system_capability_grants', [
            'user_id' => $target->id,
            'capability' => UserCapability::ORGANIZATION_VIEW->value,
            'is_active' => true,
        ]);
    }

    public function test_authorization_is_validated_inside_transaction(): void
    {
        $actor = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create(['user_id' => $actor->id, 'capability' => UserCapability::AUTHORIZATION_MANAGE]);
        $target = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create(['user_id' => $target->id, 'capability' => UserCapability::ORGANIZATION_VIEW]);

        $action = app(RevokeSystemCapability::class);

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
        $target = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create(['user_id' => $target->id, 'capability' => UserCapability::ORGANIZATION_VIEW]);

        User::where('id', $actor->id)->update(['is_active' => false]);

        $action = app(RevokeSystemCapability::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unauthorized action.');

        $action->execute($actor, $target, UserCapability::ORGANIZATION_VIEW);
    }

    public function test_stale_authorization_grant_is_rejected(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        UserSystemCapabilityGrant::factory()->create(['user_id' => $actor->id, 'capability' => UserCapability::AUTHORIZATION_MANAGE]);
        $target = User::factory()->create();
        UserSystemCapabilityGrant::factory()->create(['user_id' => $target->id, 'capability' => UserCapability::ORGANIZATION_VIEW]);

        UserSystemCapabilityGrant::where('user_id', $actor->id)->where('capability', UserCapability::AUTHORIZATION_MANAGE->value)->update(['is_active' => false]);

        $action = app(RevokeSystemCapability::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unauthorized action.');

        $action->execute($actor, $target, UserCapability::ORGANIZATION_VIEW);
    }

    public function test_unauthorized_actor_cannot_perform_no_op(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        UserSystemCapabilityGrant::factory()->create(['user_id' => $actor->id, 'capability' => UserCapability::AUTHORIZATION_MANAGE]);
        $target = User::factory()->create();
        // Target grant is inactive => no-op
        UserSystemCapabilityGrant::factory()->create(['user_id' => $target->id, 'capability' => UserCapability::ORGANIZATION_VIEW, 'is_active' => false]);

        UserSystemCapabilityGrant::where('user_id', $actor->id)->where('capability', UserCapability::AUTHORIZATION_MANAGE->value)->update(['is_active' => false]);

        $action = app(RevokeSystemCapability::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unauthorized action.');

        $action->execute($actor, $target, UserCapability::ORGANIZATION_VIEW);
    }
}
