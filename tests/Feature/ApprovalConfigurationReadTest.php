<?php

namespace Tests\Feature;

use App\Enums\CapabilityScope;
use App\Enums\UserCapability;
use App\Models\ApprovalStage;
use App\Models\ApprovalWorkflow;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalConfigurationReadTest extends TestCase
{
    use RefreshDatabase;

    private User $authorizedUser;
    private User $unauthorizedUser;
    private User $manageOnlyUser;
    private User $inactiveUser;
    private ApprovalWorkflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authorizedUser = User::factory()->create(['is_active' => true]);
        $this->unauthorizedUser = User::factory()->create(['is_active' => true]);
        $this->manageOnlyUser = User::factory()->create(['is_active' => true]);
        $this->inactiveUser = User::factory()->create(['is_active' => false]);

        UserSystemCapabilityGrant::create([
            'user_id' => $this->authorizedUser->id,
            'capability' => UserCapability::APPROVAL_CONFIGURATION_VIEW,
            'is_active' => true,
        ]);

        UserSystemCapabilityGrant::create([
            'user_id' => $this->manageOnlyUser->id,
            'capability' => UserCapability::APPROVAL_CONFIGURATION_MANAGE,
            'is_active' => true,
        ]);

        UserSystemCapabilityGrant::create([
            'user_id' => $this->inactiveUser->id,
            'capability' => UserCapability::APPROVAL_CONFIGURATION_VIEW,
            'is_active' => true,
        ]);

        $this->workflow = ApprovalWorkflow::factory()->create([
            'code' => 'WF_TEST_1',
            'name' => 'Test Workflow 1',
            'version' => 1,
            'is_active' => true,
        ]);

        ApprovalStage::factory()->create([
            'approval_workflow_id' => $this->workflow->id,
            'code' => 'STG_2',
            'sequence' => 2,
        ]);

        ApprovalStage::factory()->create([
            'approval_workflow_id' => $this->workflow->id,
            'code' => 'STG_1',
            'sequence' => 1,
        ]);
    }

    public function test_guest_cannot_access_list()
    {
        $response = $this->getJson('/settings/approval-configurations');
        $response->assertUnauthorized();
    }

    public function test_guest_cannot_access_detail()
    {
        $response = $this->getJson("/settings/approval-configurations/{$this->workflow->id}");
        $response->assertUnauthorized();
    }

    public function test_unauthorized_user_receives_403()
    {
        $response = $this->actingAs($this->unauthorizedUser)->getJson('/settings/approval-configurations');
        $response->assertForbidden();

        $response = $this->actingAs($this->unauthorizedUser)->getJson("/settings/approval-configurations/{$this->workflow->id}");
        $response->assertForbidden();
    }

    public function test_manage_only_user_cannot_read()
    {
        $response = $this->actingAs($this->manageOnlyUser)->getJson('/settings/approval-configurations');
        $response->assertForbidden();
    }

    public function test_inactive_user_cannot_read()
    {
        $response = $this->actingAs($this->inactiveUser)->getJson('/settings/approval-configurations');
        $response->assertForbidden();
    }

    public function test_inactive_grant_does_not_provide_access()
    {
        $user = User::factory()->create(['is_active' => true]);
        UserSystemCapabilityGrant::create([
            'user_id' => $user->id,
            'capability' => UserCapability::APPROVAL_CONFIGURATION_VIEW,
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->getJson('/settings/approval-configurations');
        $response->assertForbidden();
    }

    public function test_department_scope_grant_does_not_provide_access()
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->capabilityGrants()->create([
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN,
            'department_id' => \App\Models\Department::factory()->create()->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->getJson('/settings/approval-configurations');
        $response->assertForbidden();
    }

    public function test_fake_actor_id_in_request_is_ignored()
    {
        $response = $this->actingAs($this->unauthorizedUser)->getJson('/settings/approval-configurations?actor_user_id=' . $this->authorizedUser->id);
        $response->assertForbidden();
    }

    public function test_non_existent_workflow_returns_404()
    {
        $response = $this->actingAs($this->authorizedUser)->getJson('/settings/approval-configurations/99999');
        $response->assertNotFound();
    }

    public function test_unauthorized_user_does_not_leak_existence_if_not_found()
    {
        $response = $this->actingAs($this->unauthorizedUser)->getJson("/settings/approval-configurations/{$this->workflow->id}");
        $response->assertForbidden();
    }

    public function test_authorized_user_can_view_list_with_pagination()
    {
        ApprovalWorkflow::factory()->count(20)->create();

        $response = $this->actingAs($this->authorizedUser)->getJson('/settings/approval-configurations');
        $response->assertOk();
        
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'code', 'name', 'description', 'version', 'is_active', 'is_default', 'published_at']
            ],
            'meta' => [
                'current_page',
                'last_page',
                'total'
            ]
        ]);
        
        $this->assertCount(15, $response->json('data'));
    }

    public function test_authorized_user_can_view_detail_with_deterministic_stage_ordering()
    {
        $response = $this->actingAs($this->authorizedUser)->getJson("/settings/approval-configurations/{$this->workflow->id}");
        $response->assertOk();
        
        $data = $response->json('data');
        
        $this->assertEquals($this->workflow->id, $data['id']);
        $this->assertCount(2, $data['stages']);
        
        $this->assertEquals(1, $data['stages'][0]['sequence']);
        $this->assertEquals('STG_1', $data['stages'][0]['code']);
        $this->assertEquals(2, $data['stages'][1]['sequence']);
        $this->assertEquals('STG_2', $data['stages'][1]['code']);
    }

    public function test_sensitive_fields_do_not_leak()
    {
        $response = $this->actingAs($this->authorizedUser)->getJson("/settings/approval-configurations/{$this->workflow->id}");
        $response->assertOk();
        
        $data = $response->json('data');
        
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('remember_token', $data);
    }
}
