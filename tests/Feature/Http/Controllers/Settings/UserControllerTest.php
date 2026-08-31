<?php

namespace Tests\Feature\Http\Controllers\Settings;

use App\Actions\Users\CreateUserWithInvitation;
use App\Actions\Users\SendUserInvitation;
use App\Actions\Users\UpdateUser;
use App\Enums\UserCapability;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $employee;

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

        $this->employee = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::EMPLOYEE,
        ]);
    }

    public function test_authorized_user_can_view_index()
    {
        $response = $this->actingAs($this->admin)->get(route('settings.users.index'));
        $response->assertOk();
        $response->assertViewIs('settings.users.index');
    }

    public function test_unauthorized_user_cannot_view_index()
    {
        $response = $this->actingAs($this->employee)->get(route('settings.users.index'));
        $response->assertForbidden();
    }

    public function test_admin_without_capabilities_cannot_view_index()
    {
        $fakeAdmin = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($fakeAdmin)->get(route('settings.users.index'));
        $response->assertForbidden();
    }

    public function test_authorized_user_can_view_create()
    {
        $response = $this->actingAs($this->admin)->get(route('settings.users.create'));
        $response->assertOk();
        $response->assertViewIs('settings.users.create');
    }

    public function test_store_creates_user_and_redirects()
    {
        $department = Department::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)->post(route('settings.users.store'), [
            'name' => 'Alice Test',
            'email' => 'alice@test.com',
            'role' => UserRole::EMPLOYEE->value,
            'department_id' => $department->id,
        ]);

        $response->assertRedirect(route('settings.users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'alice@test.com',
            'name' => 'Alice Test',
        ]);
    }

    public function test_store_rejects_missing_department_for_employee()
    {
        $response = $this->actingAs($this->admin)->post(route('settings.users.store'), [
            'name' => 'Alice Test',
            'email' => 'alice@test.com',
            'role' => UserRole::EMPLOYEE->value,
            'department_id' => '',
        ]);

        $response->assertSessionHasErrors(['department_id']);
    }

    public function test_store_rejects_injected_password()
    {
        $department = Department::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)->post(route('settings.users.store'), [
            'name' => 'Bob Test',
            'email' => 'bob@test.com',
            'role' => UserRole::EMPLOYEE->value,
            'department_id' => $department->id,
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_resend_throttle_returns_warning()
    {
        $user = User::factory()->create(['is_active' => true, 'must_set_password' => true]);

        // Mock the action to return throttled
        $mockAction = $this->createStub(SendUserInvitation::class);
        $mockAction->method('execute')->willReturn(Password::RESET_THROTTLED);
        $this->app->instance(SendUserInvitation::class, $mockAction);

        $response = $this->actingAs($this->admin)->post(route('settings.users.invitation', $user->id));

        $response->assertRedirect();
        $response->assertSessionHas('warning', 'Davet kısa süre önce gönderildi. Lütfen tekrar denemeden önce bekleyin.');
    }

    public function test_store_exact_duplicate_email_http_error()
    {
        $department = Department::factory()->create(['is_active' => true]);
        User::factory()->create(['email' => 'user@example.com']);

        $response = $this->actingAs($this->admin)->post(route('settings.users.store'), [
            'name' => 'John Doe',
            'email' => 'user@example.com',
            'role' => UserRole::EMPLOYEE->value,
            'department_id' => $department->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'email' => 'Bu e-posta adresi ile kayıtlı bir kullanıcı zaten mevcut.',
        ]);

        $this->assertEquals(3, User::count()); // admin + employee + existing
    }

    public function test_store_duplicate_email_http_error()
    {
        $department = Department::factory()->create(['is_active' => true]);
        User::factory()->create(['email' => 'Mixed@Example.com']);

        $response = $this->actingAs($this->admin)->post(route('settings.users.store'), [
            'name' => 'John Doe',
            'email' => 'mixed@example.com',
            'role' => UserRole::EMPLOYEE->value,
            'department_id' => $department->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'email' => 'Bu e-posta adresi ile kayıtlı bir kullanıcı zaten mevcut.',
        ]);

        $this->assertEquals(3, User::count()); // admin + employee + existing
    }

    public function test_store_raw_exception_not_exposed()
    {
        $department = Department::factory()->create(['is_active' => true]);

        $mockAction = $this->createStub(CreateUserWithInvitation::class);
        $mockAction->method('execute')->willThrowException(new \Exception('SECRET CREATE INTERNAL ERROR'));
        $this->app->instance(CreateUserWithInvitation::class, $mockAction);

        $response = $this->actingAs($this->admin)->post(route('settings.users.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'role' => UserRole::EMPLOYEE->value,
            'department_id' => $department->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'İşlem tamamlanamadı. Lütfen tekrar deneyin.');

        $sessionError = session('error');
        $this->assertStringNotContainsString('SECRET CREATE INTERNAL ERROR', $sessionError);
    }

    public function test_raw_exception_not_exposed_on_update()
    {
        $user = User::factory()->create(['is_active' => true]);

        $mockAction = $this->createStub(UpdateUser::class);
        $mockAction->method('execute')->willThrowException(new \Exception('Raw exception message'));
        $this->app->instance(UpdateUser::class, $mockAction);

        $response = $this->actingAs($this->admin)->patch(route('settings.users.update', $user->id), [
            'name' => 'Name',
            'email' => 'email@example.com',
            'role' => UserRole::EMPLOYEE->value,
            'department_id' => Department::factory()->create(['is_active' => true])->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'İşlem tamamlanamadı. Lütfen tekrar deneyin.');
    }

    public function test_inactive_pending_target_resend_shows_safe_error()
    {
        $user = User::factory()->create(['is_active' => false, 'must_set_password' => true]);

        $response = $this->actingAs($this->admin)->post(route('settings.users.invitation', $user->id));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Davet gönderebilmek için kullanıcı hesabı aktif olmalıdır.');

        $response->assertSessionDoesntHaveErrors(); // Or we can check the error message doesn't contain 'Target user is not active.'
    }

    public function test_ready_active_target_resend_shows_safe_error()
    {
        $user = User::factory()->create(['is_active' => true, 'must_set_password' => false]);

        $response = $this->actingAs($this->admin)->post(route('settings.users.invitation', $user->id));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Bu kullanıcının hesap kurulumu tamamlanmıştır.');
    }
}
