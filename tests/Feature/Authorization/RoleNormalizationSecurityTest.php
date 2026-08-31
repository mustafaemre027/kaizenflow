<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserCapability;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleNormalizationSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $opex;

    private User $manager;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);
        $this->opex = User::factory()->create(['role' => UserRole::OPEX_SPECIALIST, 'is_active' => true]);
        $this->manager = User::factory()->create(['role' => UserRole::MANAGER, 'is_active' => true]);
        $this->employee = User::factory()->create(['role' => UserRole::EMPLOYEE, 'is_active' => true]);
    }

    public function test_settings_authorization_matrix()
    {
        // A. ADMIN without organization.view -> list/read DENIED
        $this->assertFalse($this->admin->can('viewAny', Category::class));
        $this->assertFalse($this->admin->can('viewAny', Department::class));

        // B. ADMIN without organization.manage -> create/update DENIED
        $this->assertFalse($this->admin->can('create', Category::class));
        $this->assertFalse($this->admin->can('create', Department::class));

        // C. EMPLOYEE + organization.view -> list/read ALLOWED
        $this->employee->systemCapabilityGrants()->create(['capability' => UserCapability::ORGANIZATION_VIEW, 'is_active' => true]);
        $this->assertTrue($this->employee->can('viewAny', Category::class));

        // D. EMPLOYEE + organization.view only -> create/update DENIED
        $this->assertFalse($this->employee->can('create', Category::class));

        // E. EMPLOYEE + organization.manage only -> read/create/update DENIED
        $this->employee->systemCapabilityGrants()->delete();
        $this->employee->systemCapabilityGrants()->create(['capability' => UserCapability::ORGANIZATION_MANAGE, 'is_active' => true]);
        $this->assertFalse($this->employee->can('viewAny', Category::class));
        $this->assertFalse($this->employee->can('create', Category::class));

        // F. EMPLOYEE + view + manage -> create/update ALLOWED
        $this->employee->systemCapabilityGrants()->create(['capability' => UserCapability::ORGANIZATION_VIEW, 'is_active' => true]);
        $this->assertTrue($this->employee->can('create', Category::class));
    }

    public function test_kaizen_privilege_matrix()
    {
        $departmentA = Department::factory()->create();
        $departmentB = Department::factory()->create();

        $kaizenA = Kaizen::factory()->create(['department_id' => $departmentA->id]);
        $kaizenB = Kaizen::factory()->create(['department_id' => $departmentB->id]);

        // A. ADMIN role alone + unrelated Kaizen -> cannot view
        $this->assertFalse($this->admin->can('view', $kaizenA));
        $this->actingAs($this->admin)->get('/kaizens')->assertDontSee($kaizenA->title);

        // B. OPEX_SPECIALIST role alone + unrelated Kaizen -> cannot view
        $this->assertFalse($this->opex->can('view', $kaizenA));
        $this->actingAs($this->opex)->get('/kaizens')->assertDontSee($kaizenA->title);

        // C. MANAGER role alone + same-department unrelated Kaizen -> cannot view
        $this->manager->update(['department_id' => $departmentA->id]);
        $this->assertFalse($this->manager->can('view', $kaizenA));
        $this->actingAs($this->manager)->get('/kaizens')->assertDontSee($kaizenA->title);

        // D. EMPLOYEE + SYSTEM KAIZEN_OPEX_REVIEW -> can view unrelated Kaizen
        $this->employee->systemCapabilityGrants()->create(['capability' => UserCapability::KAIZEN_OPEX_REVIEW, 'is_active' => true]);
        $this->assertTrue($this->employee->can('view', $kaizenA));
        $this->actingAs($this->employee)->get('/kaizens')->assertSee($kaizenA->title);

        // E. ADMIN + SYSTEM KAIZEN_OPEX_REVIEW -> same behavior as D
        $this->admin->systemCapabilityGrants()->create(['capability' => UserCapability::KAIZEN_OPEX_REVIEW, 'is_active' => true]);
        $this->assertTrue($this->admin->can('view', $kaizenA));
        $this->actingAs($this->admin)->get('/kaizens')->assertSee($kaizenA->title);

        // F. EMPLOYEE + DEPARTMENT KAIZEN_DEPARTMENT_APPROVE dept A -> can view dept A Kaizen, cannot view dept B
        $this->employee->systemCapabilityGrants()->delete(); // Clear OPEX review
        $this->employee->capabilityGrants()->create(['capability' => UserCapability::KAIZEN_DEPARTMENT_APPROVE, 'department_id' => $departmentA->id, 'is_active' => true]);
        $this->assertTrue($this->employee->can('view', $kaizenA));
        $this->assertFalse($this->employee->can('view', $kaizenB));
        $this->actingAs($this->employee)->get('/kaizens')->assertSee($kaizenA->title)->assertDontSee($kaizenB->title);

        // G. MANAGER + exact same department capability -> same behavior as F
        $this->manager->capabilityGrants()->create(['capability' => UserCapability::KAIZEN_DEPARTMENT_APPROVE, 'department_id' => $departmentA->id, 'is_active' => true]);
        $this->assertTrue($this->manager->can('view', $kaizenA));
        $this->assertFalse($this->manager->can('view', $kaizenB));

        // H. inactive capability grant -> no privilege
        $this->manager->capabilityGrants()->update(['is_active' => false]);
        $this->assertFalse($this->manager->can('view', $kaizenA));

        // I. creator -> can still view own record
        $ownKaizen = Kaizen::factory()->create(['creator_user_id' => $this->manager->id]);
        $this->assertTrue($this->manager->can('view', $ownKaizen));

        // J. assigned user -> can still view assigned record
        $assignedKaizen = Kaizen::factory()->create(['assigned_user_id' => $this->manager->id]);
        $this->assertTrue($this->manager->can('view', $assignedKaizen));
    }

    public function test_filter_metadata_leakage()
    {
        $departmentA = Department::factory()->create(['name' => 'Alpha Dept']);
        $departmentB = Department::factory()->create(['name' => 'Beta Dept']);

        Kaizen::factory()->create(['department_id' => $departmentA->id]);
        Kaizen::factory()->create(['department_id' => $departmentB->id]);

        $this->employee->capabilityGrants()->create(['capability' => UserCapability::KAIZEN_DEPARTMENT_APPROVE, 'department_id' => $departmentA->id, 'is_active' => true]);

        // Employee should only see Alpha Dept in filters
        $this->actingAs($this->employee)->get('/kaizens')
            ->assertSee('Alpha Dept')
            ->assertDontSee('Beta Dept');

        // Opex Reviewer should see both
        $this->opex->systemCapabilityGrants()->create(['capability' => UserCapability::KAIZEN_OPEX_REVIEW, 'is_active' => true]);
        $this->actingAs($this->opex)->get('/kaizens')
            ->assertSee('Alpha Dept')
            ->assertSee('Beta Dept');
    }
}
