<?php

namespace Tests\Feature\Actions\Users;

use App\Actions\Users\UpdateUser;
use App\Enums\UserCapability;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Models\UserCapabilityGrant;
use App\Models\UserSystemCapabilityGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UpdateUserTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $target;

    private UpdateUser $action;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->admin = User::factory()->create(['is_active' => true]);
        UserSystemCapabilityGrant::create([
            'user_id' => $this->admin->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
            'is_active' => true,
        ]);

        $this->target = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'role' => UserRole::EMPLOYEE,
            'department_id' => Department::factory()->create()->id,
            'is_active' => true,
            'must_set_password' => false,
            'email_verified_at' => now(),
        ]);

        $this->action = app(UpdateUser::class);
    }

    public function test_it_updates_name_and_role()
    {
        $result = $this->action->execute($this->admin, $this->target, [
            'name' => 'New Name',
            'email' => 'old@example.com',
            'role' => UserRole::MANAGER->value,
            'department_id' => $this->target->department_id,
        ]);

        $this->assertTrue($result['success']);

        $this->assertDatabaseHas('users', [
            'id' => $this->target->id,
            'name' => 'New Name',
            'role' => UserRole::MANAGER->value,
            'email' => 'old@example.com',
            'email_verified_at' => $this->target->email_verified_at->format('Y-m-d H:i:s'),
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'user.updated',
            'actor_user_id' => $this->admin->id,
            'auditable_id' => $this->target->id,
        ]);
    }

    public function test_it_rejects_department_change_if_active_department_grants_exist()
    {
        UserCapabilityGrant::create([
            'user_id' => $this->target->id,
            'department_id' => $this->target->department_id,
            'capability' => UserCapability::KAIZEN_DEPARTMENT_APPROVE,
            'is_active' => true,
        ]);

        $newDept = Department::factory()->create();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('departman yetkileri');

        $this->action->execute($this->admin, $this->target, [
            'name' => 'New Name',
            'email' => 'old@example.com',
            'role' => UserRole::EMPLOYEE->value,
            'department_id' => $newDept->id,
        ]);
    }

    public function test_it_allows_department_change_if_no_active_grants()
    {
        UserCapabilityGrant::create([
            'user_id' => $this->target->id,
            'department_id' => $this->target->department_id,
            'capability' => UserCapability::KAIZEN_DEPARTMENT_APPROVE,
            'is_active' => false,
        ]);

        $newDept = Department::factory()->create();

        $result = $this->action->execute($this->admin, $this->target, [
            'name' => 'New Name',
            'email' => 'old@example.com',
            'role' => UserRole::EMPLOYEE->value,
            'department_id' => $newDept->id,
        ]);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('users', [
            'id' => $this->target->id,
            'department_id' => $newDept->id,
        ]);
    }

    public function test_ready_user_email_change_invalidates_state()
    {
        DB::table('password_reset_tokens')->insert([
            'email' => $this->target->email,
            'token' => 'dummy',
            'created_at' => now(),
        ]);
        EmailVerificationCode::create([
            'user_id' => $this->target->id,
            'code_hash' => 'hash',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $result = $this->action->execute($this->admin, $this->target, [
            'name' => 'Old Name',
            'email' => 'new@example.com',
            'role' => UserRole::EMPLOYEE->value,
            'department_id' => $this->target->department_id,
        ]);

        $this->assertTrue($result['success']);

        $this->assertDatabaseHas('users', [
            'id' => $this->target->id,
            'email' => 'new@example.com',
            'email_verified_at' => null,
            'must_set_password' => false,
        ]);

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'old@example.com',
        ]);

        // Wait, issue code creates a new one
        $this->assertDatabaseHas('email_verification_codes', [
            'user_id' => $this->target->id,
        ]);
    }

    public function test_pending_user_email_change_invalidates_invitation()
    {
        $this->target->must_set_password = true;
        $this->target->invitation_sent_at = now()->subDay();
        $this->target->save();

        $result = $this->action->execute($this->admin, $this->target, [
            'name' => 'Old Name',
            'email' => 'newpending@example.com',
            'role' => UserRole::EMPLOYEE->value,
            'department_id' => $this->target->department_id,
        ]);

        $this->assertTrue($result['success']);

        $this->assertDatabaseHas('users', [
            'id' => $this->target->id,
            'email' => 'newpending@example.com',
            'must_set_password' => true,
        ]);

        $fresh = $this->target->fresh();
        $this->assertNotNull($fresh->invitation_sent_at);
        $this->assertTrue($fresh->invitation_sent_at->isToday());
    }

    public function test_it_does_not_audit_no_op_update()
    {
        $result = $this->action->execute($this->admin, $this->target, [
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'role' => UserRole::EMPLOYEE->value,
            'department_id' => $this->target->department_id,
        ]);

        $this->assertEquals('Değişiklik yapılmadı.', $result['message']);

        $this->assertDatabaseMissing('audit_logs', [
            'event' => 'user.updated',
            'auditable_id' => $this->target->id,
        ]);
    }
}
