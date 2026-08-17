<?php

namespace Tests\Feature\Models;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_table_has_expected_domain_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('users', [
            'department_id', 'role', 'is_active',
        ]));
    }

    public function test_new_user_has_default_role_and_is_active(): void
    {
        User::unguard();
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        User::reguard();
        $user->refresh();

        $this->assertSame(UserRole::EMPLOYEE, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertIsBool($user->is_active);
    }

    public function test_user_can_be_created_without_department(): void
    {
        User::unguard();
        $user = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'department_id' => null,
        ]);
        User::reguard();

        $this->assertNull($user->department_id);
    }

    public function test_user_can_be_associated_with_department(): void
    {
        $department = Department::create([
            'name' => 'IT',
            'code' => 'IT01',
        ]);

        User::unguard();
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);
        User::reguard();

        $user->department()->associate($department);
        $user->save();

        $this->assertTrue($user->department->is($department));
        $this->assertCount(1, $department->users);
        $this->assertTrue($department->users->first()->is($user));
    }

    public function test_prevent_deletion_of_department_if_users_are_attached(): void
    {
        $department = Department::create([
            'name' => 'HR',
            'code' => 'HR01',
        ]);

        User::unguard();
        $user = User::create([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => 'password',
        ]);
        User::reguard();

        $user->department()->associate($department);
        $user->save();

        $this->expectException(QueryException::class);
        $department->delete();
    }

    public function test_domain_fields_are_not_fillable(): void
    {
        $user = new User;
        $fillable = $user->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password', $fillable);

        $this->assertNotContains('role', $fillable);
        $this->assertNotContains('is_active', $fillable);
        $this->assertNotContains('department_id', $fillable);
    }
}
