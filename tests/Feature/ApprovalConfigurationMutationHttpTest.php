<?php

namespace Tests\Feature;

use App\Enums\UserCapability;
use App\Models\ApprovalStage;
use App\Models\ApprovalWorkflow;
use App\Models\Department;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use App\Services\AppendAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery\MockInterface;
use App\Exceptions\DomainException;

class ApprovalConfigurationMutationHttpTest extends TestCase
{
    use RefreshDatabase;

    private User $authorizedUser;
    private User $unauthorizedUser;
    private User $viewOnlyUser;
    private User $inactiveUser;
    private ApprovalWorkflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authorizedUser = User::factory()->create(['is_active' => true]);
        $this->unauthorizedUser = User::factory()->create(['is_active' => true]);
        $this->viewOnlyUser = User::factory()->create(['is_active' => true]);
        $this->inactiveUser = User::factory()->create(['is_active' => false]);

        UserSystemCapabilityGrant::create([
            'user_id' => $this->authorizedUser->id,
            'capability' => UserCapability::APPROVAL_CONFIGURATION_MANAGE,
            'is_active' => true,
        ]);

        UserSystemCapabilityGrant::create([
            'user_id' => $this->viewOnlyUser->id,
            'capability' => UserCapability::APPROVAL_CONFIGURATION_VIEW,
            'is_active' => true,
        ]);

        UserSystemCapabilityGrant::create([
            'user_id' => $this->inactiveUser->id,
            'capability' => UserCapability::APPROVAL_CONFIGURATION_MANAGE,
            'is_active' => true,
        ]);

        $this->workflow = ApprovalWorkflow::factory()->create([
            'code' => 'WF_TEST_MUT',
            'name' => 'Test Mut',
            'version' => 1,
            'is_active' => false,
            'published_at' => null,
        ]);
        
        ApprovalStage::factory()->create([
            'approval_workflow_id' => $this->workflow->id,
            'code' => 'STG_1',
            'sequence' => 1,
            'is_final' => true,
            'is_active' => true,
        ]);
    }

    private function validPayload(): array
    {
        return [
            'code' => 'WF_NEW',
            'name' => 'New WF',
            'description' => 'Desc',
            'stages' => [
                [
                    'code' => 'ST1',
                    'name' => 'Stage 1',
                    'sequence' => 1,
                    'is_final' => true,
                ],
            ],
        ];
    }

    // 1-8 Authentication & Authorization
    public function test_guest_cannot_mutate()
    {
        $this->postJson('/settings/approval-configurations', $this->validPayload())->assertUnauthorized();
    }

    public function test_view_only_user_cannot_mutate()
    {
        $this->actingAs($this->viewOnlyUser)
             ->postJson('/settings/approval-configurations', $this->validPayload())
             ->assertForbidden();
    }

    public function test_role_admin_without_grant_cannot_mutate()
    {
        $admin = User::factory()->create(['is_active' => true]); // role might be implicit
        $this->actingAs($admin)
             ->postJson('/settings/approval-configurations', $this->validPayload())
             ->assertForbidden();
    }

    public function test_inactive_user_cannot_mutate()
    {
        $this->actingAs($this->inactiveUser)
             ->postJson('/settings/approval-configurations', $this->validPayload())
             ->assertForbidden();
    }

    public function test_inactive_manage_grant_cannot_mutate()
    {
        $user = User::factory()->create(['is_active' => true]);
        UserSystemCapabilityGrant::create([
            'user_id' => $user->id,
            'capability' => UserCapability::APPROVAL_CONFIGURATION_MANAGE,
            'is_active' => false,
        ]);
        $this->actingAs($user)
             ->postJson('/settings/approval-configurations', $this->validPayload())
             ->assertForbidden();
    }

    public function test_active_manage_grant_can_mutate()
    {
        $this->actingAs($this->authorizedUser)
             ->postJson('/settings/approval-configurations', $this->validPayload())
             ->assertCreated();
    }

    public function test_fake_actor_id_in_body_is_ignored()
    {
        $payload = $this->validPayload();
        $payload['actor_user_id'] = $this->authorizedUser->id; // invalid prohibited field

        $response = $this->actingAs($this->unauthorizedUser)
             ->postJson('/settings/approval-configurations', $payload);
        
        $response->assertForbidden();
    }

    public function test_unauthorized_user_with_invalid_payload_gets_403()
    {
        $this->actingAs($this->unauthorizedUser)
             ->postJson('/settings/approval-configurations', [])
             ->assertForbidden();
    }

    public function test_department_capability_cannot_bypass_system_manage()
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->capabilityGrants()->create([
            'capability' => UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN,
            'department_id' => Department::factory()->create()->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
             ->postJson('/settings/approval-configurations', $this->validPayload())
             ->assertForbidden();
    }

    // 9-11 IDOR
    public function test_unauthorized_user_gets_403_for_existing_id()
    {
        $this->actingAs($this->unauthorizedUser)
             ->patchJson("/settings/approval-configurations/{$this->workflow->id}", $this->validPayload())
             ->assertForbidden();
    }

    public function test_unauthorized_user_gets_403_for_non_existing_id()
    {
        $this->actingAs($this->unauthorizedUser)
             ->patchJson("/settings/approval-configurations/99999", $this->validPayload())
             ->assertForbidden();
    }

    public function test_authorized_user_gets_404_for_non_existing_id()
    {
        $this->actingAs($this->authorizedUser)
             ->patchJson("/settings/approval-configurations/99999", $this->validPayload())
             ->assertNotFound();
    }

    // 12-16 Validation
    public function test_missing_or_invalid_fields_yield_422()
    {
        $this->actingAs($this->authorizedUser)
             ->postJson('/settings/approval-configurations', [])
             ->assertUnprocessable();
    }

    public function test_nested_stages_payload_limit()
    {
        $payload = $this->validPayload();
        $payload['stages'] = array_fill(0, 51, $payload['stages'][0]); // Exceed max size (assume 50 max)

        $this->actingAs($this->authorizedUser)
             ->postJson('/settings/approval-configurations', $payload)
             ->assertUnprocessable();
    }

    public function test_duplicate_stage_sequence_rejected()
    {
        $payload = $this->validPayload();
        $payload['stages'][] = [
            'code' => 'ST2',
            'name' => 'Stage 2',
            'sequence' => 1, // Duplicate
            'is_final' => false,
        ];

        // 422 structurally 
        $response = $this->actingAs($this->authorizedUser)
             ->postJson('/settings/approval-configurations', $payload);
             
        $this->assertEquals(422, $response->status());
    }

    public function test_prohibited_system_fields_rejected()
    {
        $payload = $this->validPayload();
        $payload['published_at'] = '2025-01-01';
        $payload['is_active'] = true;
        
        $this->actingAs($this->authorizedUser)
             ->postJson('/settings/approval-configurations', $payload)
             ->assertUnprocessable();
    }

    public function test_validation_failure_creates_no_garbage()
    {
        $countBefore = ApprovalWorkflow::count();
        $this->actingAs($this->authorizedUser)
             ->postJson('/settings/approval-configurations', ['code' => '']);
        $this->assertEquals($countBefore, ApprovalWorkflow::count());
    }

    // 17-22 Lifecycle operations
    public function test_create_draft_endpoint()
    {
        $response = $this->actingAs($this->authorizedUser)
             ->postJson('/settings/approval-configurations', $this->validPayload());
        
        $response->assertCreated();
        $this->assertDatabaseHas('approval_workflows', ['code' => 'WF_NEW', 'version' => 1]);
    }

    public function test_update_draft_endpoint()
    {
        $stageId = ApprovalStage::where('approval_workflow_id', $this->workflow->id)->first()->id;
        $payload = [
            'name' => 'Updated Name',
            'description' => $this->workflow->description,
            'stages' => [
                [
                    'id' => $stageId,
                    'code' => 'STG_1',
                    'name' => 'Updated Stage',
                    'description' => null,
                    'sequence' => 1,
                    'is_final' => true,
                ]
            ]
        ];
        
        $response = $this->actingAs($this->authorizedUser)
             ->patchJson("/settings/approval-configurations/{$this->workflow->id}", $payload);
             
        $response->assertOk();
        $this->assertDatabaseHas('approval_workflows', ['id' => $this->workflow->id, 'name' => 'Updated Name']);
    }

    public function test_publish_endpoint()
    {
        $response = $this->actingAs($this->authorizedUser)
             ->postJson("/settings/approval-configurations/{$this->workflow->id}/publish");
             
        $response->assertOk();
        $this->assertTrue(ApprovalWorkflow::find($this->workflow->id)->is_active);
    }

    public function test_set_default_endpoint()
    {
        $this->workflow->update(['is_active' => true, 'published_at' => now()]);
        
        $response = $this->actingAs($this->authorizedUser)
             ->postJson("/settings/approval-configurations/{$this->workflow->id}/default");
             
        $response->assertOk();
        $this->assertTrue(ApprovalWorkflow::find($this->workflow->id)->is_default);
    }

    public function test_deactivate_endpoint()
    {
        $this->workflow->update(['is_active' => true, 'published_at' => now()]);
        
        $response = $this->actingAs($this->authorizedUser)
             ->postJson("/settings/approval-configurations/{$this->workflow->id}/deactivate");
             
        $response->assertOk();
        $this->assertFalse(ApprovalWorkflow::find($this->workflow->id)->is_active);
    }

    // 23-26 Rollbacks and no-ops
    public function test_noop_update_generates_no_audit()
    {
        $stage = ApprovalStage::where('approval_workflow_id', $this->workflow->id)->first();
        $payload = [
            'name' => $this->workflow->name,
            'description' => $this->workflow->description,
            'stages' => [
                [
                    'id' => $stage->id,
                    'code' => $stage->code,
                    'name' => $stage->name,
                    'description' => $stage->description,
                    'sequence' => $stage->sequence,
                    'is_final' => $stage->is_final,
                ]
            ]
        ];

        $auditCount = \Illuminate\Support\Facades\DB::table('audit_logs')->count();

        $auditCount = \Illuminate\Support\Facades\DB::table('audit_logs')->count();

        $this->actingAs($this->authorizedUser)
             ->patchJson("/settings/approval-configurations/{$this->workflow->id}", $payload)
             ->assertOk();

        $this->assertEquals($auditCount, \Illuminate\Support\Facades\DB::table('audit_logs')->count());
    }
    
    public function test_domain_rejection_causes_no_mutation()
    {
        $workflow2 = ApprovalWorkflow::factory()->create([
            'code' => 'WF_TEST_INV',
            'name' => 'Test Inv',
            'version' => 1,
            'is_active' => false,
            'published_at' => null,
        ]);
        
        $response = $this->actingAs($this->authorizedUser)
             ->postJson("/settings/approval-configurations/{$workflow2->id}/publish");
             
        $response->assertStatus(422)
                 ->assertJsonPath('message', 'Domain rule violation')
                 ->assertJsonMissing(['error' => 'Cannot publish workflow without any active stages.']); // Raw message should not leak
    }

    public function test_audit_failure_rolls_back_mutation()
    {
        $this->mock(AppendAuditLog::class, function (MockInterface $mock) {
            $mock->shouldReceive('execute')->andThrow(new \Exception('Audit failed'));
        });

        $response = $this->actingAs($this->authorizedUser)
             ->postJson('/settings/approval-configurations', $this->validPayload());
             
        $response->assertStatus(500);
        $this->assertDatabaseMissing('approval_workflows', ['code' => 'WF_NEW']);
    }

    // 27-30 HTTP boundaries
    public function test_wrong_http_method_yields_405()
    {
        $this->actingAs($this->authorizedUser)
             ->putJson("/settings/approval-configurations/{$this->workflow->id}", [])
             ->assertStatus(405);
    }

    public function test_response_does_not_leak_sql_state_or_path()
    {
        $payload = $this->validPayload();
        $payload['code'] = str_repeat('A', 500); // Too long
        
        $response = $this->actingAs($this->authorizedUser)
             ->postJson('/settings/approval-configurations', $payload);
             
        $content = $response->getContent();
        $this->assertStringNotContainsString('SQLSTATE', $content);
        $this->assertStringNotContainsString('.env', $content);
        $this->assertStringNotContainsString(base_path(), $content);
    }
}
