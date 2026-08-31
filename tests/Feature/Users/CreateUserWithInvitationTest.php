<?php

namespace Tests\Feature\Users;

use App\Actions\Users\CreateUserWithInvitation;
use App\Actions\Users\SendUserInvitation;
use App\Enums\UserCapability;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery\MockInterface;
use Tests\TestCase;

class CreateUserWithInvitationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private CreateUserWithInvitation $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_active' => true]);

        UserSystemCapabilityGrant::create([
            'user_id' => $this->admin->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
            'is_active' => true,
        ]);
        UserSystemCapabilityGrant::create([
            'user_id' => $this->admin->id,
            'capability' => UserCapability::ORGANIZATION_VIEW,
            'is_active' => true,
        ]);

        $this->action = app(CreateUserWithInvitation::class);
    }

    public function test_it_creates_user_and_sends_invitation_successfully()
    {
        $department = Department::factory()->create();

        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => UserRole::EMPLOYEE,
            'department_id' => $department->id,
        ];

        $result = $this->action->execute($this->admin, $payload);

        $this->assertTrue($result['success']);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals(UserRole::EMPLOYEE, $user->role);
        $this->assertEquals($department->id, $user->department_id);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->must_set_password);
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->invitation_sent_at);

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'user.created',
            'actor_user_id' => $this->admin->id,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'user.invitation_sent',
            'actor_user_id' => $this->admin->id,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);
    }

    public function test_it_prevents_duplicate_email_creation()
    {
        User::factory()->create(['email' => 'Mixed@Example.com']);

        $payload = [
            'name' => 'John Doe',
            'email' => 'mixed@example.com',
            'role' => UserRole::EMPLOYEE,
            'department_id' => null,
        ];

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Bu e-posta adresi ile kayıtlı bir kullanıcı zaten mevcut.');
        $this->action->execute($this->admin, $payload);
    }

    public function test_it_handles_mail_failure_gracefully()
    {
        $mock = $this->mock(SendUserInvitation::class, function (MockInterface $mock) {
            $mock->shouldReceive('execute')->andThrow(new \Exception('Mail server down'));
        });

        $actionWithMock = new CreateUserWithInvitation($mock);

        $payload = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'role' => UserRole::EMPLOYEE,
            'department_id' => null,
        ];

        $result = $actionWithMock->execute($this->admin, $payload);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('davet gönderilemedi', $result['message']);

        // Ensure user is created and kept in pending state
        $user = User::where('email', 'jane@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->invitation_sent_at);
        $this->assertTrue($user->must_set_password);
    }

    public function test_placeholder_password_is_hashed_and_secure()
    {
        $payload = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => UserRole::EMPLOYEE,
            'department_id' => null,
        ];

        $this->action->execute($this->admin, $payload);

        $user = User::where('email', 'test@example.com')->first();

        $this->assertTrue(Hash::info($user->password)['algoName'] !== 'unknown');
        // We do not know the plaintext, which is the point
        $this->assertFalse(Hash::check('password', $user->password));
    }
}
