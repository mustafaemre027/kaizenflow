<?php

namespace Tests\Feature\Policies;

use App\Enums\KaizenStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\User;
use App\Policies\KaizenPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class KaizenPolicyTest extends TestCase
{
    use RefreshDatabase;

    private KaizenPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new KaizenPolicy;
    }

    public function test_laravel_policy_auto_discovery_maps_kaizen_model_to_kaizen_policy(): void
    {
        $policy = Gate::getPolicyFor(Kaizen::class);
        $this->assertInstanceOf(KaizenPolicy::class, $policy);
    }

    public function test_active_canonical_roles_can_view_any(): void
    {
        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create([
                'is_active' => true,
                'role' => $role,
            ]);
            $this->assertTrue($this->policy->viewAny($user));
        }
    }

    public function test_inactive_user_cannot_view_any(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
            'role' => UserRole::EMPLOYEE,
        ]);
        $this->assertFalse($this->policy->viewAny($user));
    }

    public function test_creator_can_view_own_kaizen(): void
    {
        $creator = User::factory()->create(['is_active' => true]);
        $kaizen = Kaizen::factory()->create(['creator_user_id' => $creator->id]);

        $this->assertTrue($this->policy->view($creator, $kaizen));
    }

    public function test_assigned_user_can_view_assigned_kaizen(): void
    {
        $assignee = User::factory()->create(['is_active' => true]);
        $kaizen = Kaizen::factory()->assignedTo($assignee)->create();

        $this->assertTrue($this->policy->view($assignee, $kaizen));
    }

    public function test_opex_specialist_can_view_any_kaizen(): void
    {
        $opex = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::OPEX_SPECIALIST,
        ]);
        $kaizen = Kaizen::factory()->create();

        $this->assertTrue($this->policy->view($opex, $kaizen));
    }

    public function test_admin_can_view_any_kaizen(): void
    {
        $admin = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::ADMIN,
        ]);
        $kaizen = Kaizen::factory()->create();

        $this->assertTrue($this->policy->view($admin, $kaizen));
    }

    public function test_manager_in_same_department_can_view_kaizen(): void
    {
        $department = Department::factory()->create();
        $manager = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::MANAGER,
            'department_id' => $department->id,
        ]);
        $kaizen = Kaizen::factory()->create([
            'department_id' => $department->id,
        ]);

        $this->assertTrue($this->policy->view($manager, $kaizen));
    }

    public function test_manager_in_different_department_cannot_view_kaizen(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $manager = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::MANAGER,
            'department_id' => $department1->id,
        ]);
        $kaizen = Kaizen::factory()->create([
            'department_id' => $department2->id,
        ]);

        $this->assertFalse($this->policy->view($manager, $kaizen));
    }

    public function test_employee_in_different_department_cannot_view_kaizen(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $employee = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::EMPLOYEE,
            'department_id' => $department1->id,
        ]);
        $kaizen = Kaizen::factory()->create([
            'department_id' => $department2->id,
        ]);

        $this->assertFalse($this->policy->view($employee, $kaizen));
    }

    public function test_active_user_with_active_department_can_create(): void
    {
        $department = Department::factory()->create(['is_active' => true]);
        $user = User::factory()->create([
            'is_active' => true,
            'department_id' => $department->id,
            'role' => UserRole::EMPLOYEE,
        ]);

        $this->assertTrue($this->policy->create($user));
    }

    public function test_inactive_user_cannot_create(): void
    {
        $department = Department::factory()->create(['is_active' => true]);
        $user = User::factory()->create([
            'is_active' => false,
            'department_id' => $department->id,
        ]);

        $this->assertFalse($this->policy->create($user));
    }

    public function test_user_without_department_cannot_create(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'department_id' => null,
        ]);

        $this->assertFalse($this->policy->create($user));
    }

    public function test_user_with_inactive_department_cannot_create(): void
    {
        $department = Department::factory()->create(['is_active' => false]);
        $user = User::factory()->create([
            'is_active' => true,
            'department_id' => $department->id,
        ]);

        $this->assertFalse($this->policy->create($user));
    }

    public function test_creator_can_update_draft(): void
    {
        $creator = User::factory()->create(['is_active' => true]);
        $kaizen = Kaizen::factory()->withStatus(KaizenStatus::DRAFT)->create([
            'creator_user_id' => $creator->id,
        ]);

        $this->assertTrue($this->policy->update($creator, $kaizen));
    }

    public function test_creator_can_update_revision_requested(): void
    {
        $creator = User::factory()->create(['is_active' => true]);
        $kaizen = Kaizen::factory()->withStatus(KaizenStatus::REVISION_REQUESTED)->create([
            'creator_user_id' => $creator->id,
        ]);

        $this->assertTrue($this->policy->update($creator, $kaizen));
    }

    public function test_creator_cannot_update_other_statuses(): void
    {
        $creator = User::factory()->create(['is_active' => true]);

        $statuses = [
            KaizenStatus::SUBMITTED,
            KaizenStatus::MANAGER_REVIEW,
            KaizenStatus::APPROVED,
            KaizenStatus::IN_PROGRESS,
            KaizenStatus::COMPLETED,
            KaizenStatus::REJECTED,
        ];

        foreach ($statuses as $status) {
            $kaizen = Kaizen::factory()->withStatus($status)->create([
                'creator_user_id' => $creator->id,
            ]);
            $this->assertFalse($this->policy->update($creator, $kaizen));
        }
    }

    public function test_non_creator_cannot_update_even_with_high_role(): void
    {
        $creator = User::factory()->create(['is_active' => true]);
        $kaizen = Kaizen::factory()->withStatus(KaizenStatus::DRAFT)->create([
            'creator_user_id' => $creator->id,
        ]);

        $admin = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::ADMIN,
        ]);

        $opex = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::OPEX_SPECIALIST,
        ]);

        $manager = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::MANAGER,
        ]);

        $this->assertFalse($this->policy->update($admin, $kaizen));
        $this->assertFalse($this->policy->update($opex, $kaizen));
        $this->assertFalse($this->policy->update($manager, $kaizen));
    }

    public function test_creator_can_submit_draft_or_revision_requested(): void
    {
        $creator = User::factory()->create(['is_active' => true]);

        $kaizenDraft = Kaizen::factory()->withStatus(KaizenStatus::DRAFT)->create([
            'creator_user_id' => $creator->id,
        ]);

        $kaizenRev = Kaizen::factory()->withStatus(KaizenStatus::REVISION_REQUESTED)->create([
            'creator_user_id' => $creator->id,
        ]);

        $this->assertTrue($this->policy->submit($creator, $kaizenDraft));
        $this->assertTrue($this->policy->submit($creator, $kaizenRev));
    }

    public function test_creator_cannot_submit_other_statuses(): void
    {
        $creator = User::factory()->create(['is_active' => true]);

        $kaizen = Kaizen::factory()->withStatus(KaizenStatus::SUBMITTED)->create([
            'creator_user_id' => $creator->id,
        ]);

        $this->assertFalse($this->policy->submit($creator, $kaizen));
    }

    public function test_inactive_creator_cannot_update_or_submit(): void
    {
        $creator = User::factory()->create(['is_active' => false]);
        $kaizen = Kaizen::factory()->withStatus(KaizenStatus::DRAFT)->create([
            'creator_user_id' => $creator->id,
        ]);

        $this->assertFalse($this->policy->update($creator, $kaizen));
        $this->assertFalse($this->policy->submit($creator, $kaizen));
    }

    public function test_no_role_can_delete_restore_or_force_delete(): void
    {
        $kaizen = Kaizen::factory()->create();

        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create([
                'is_active' => true,
                'role' => $role,
            ]);

            $this->assertFalse($this->policy->delete($user, $kaizen));
            $this->assertFalse($this->policy->restore($user, $kaizen));
            $this->assertFalse($this->policy->forceDelete($user, $kaizen));
        }
    }
}
