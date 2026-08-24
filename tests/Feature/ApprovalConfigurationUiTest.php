<?php

namespace Tests\Feature;

use App\Enums\UserCapability;
use App\Models\ApprovalStage;
use App\Models\ApprovalWorkflow;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalConfigurationUiTest extends TestCase
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
            'code' => 'WF_UI_TEST',
            'name' => 'UI Test Workflow',
            'version' => 1,
            'is_active' => false,
            'published_at' => null,
        ]);

        ApprovalStage::factory()->create([
            'approval_workflow_id' => $this->workflow->id,
            'code' => 'STG_UI_1',
            'sequence' => 1,
            'is_final' => true,
            'is_active' => true,
        ]);
    }

    private function validPayload(): array
    {
        return [
            'code' => 'WF_NEW_UI',
            'name' => 'New UI WF',
            'description' => 'Desc',
            'stages' => [
                [
                    'code' => 'ST1',
                    'name' => 'Stage 1',
                    'sequence' => 2,
                    'is_final' => true,
                ],
            ],
        ];
    }

    private function validUpdatePayload(): array
    {
        return [
            'code' => 'WF_UI_TEST',
            'name' => 'UI Test Workflow Updated',
            'description' => 'Desc',
            'stages' => [
                [
                    'id' => 1,
                    'code' => 'STG_UI_1',
                    'name' => 'Stage 1 Updated',
                    'sequence' => 1,
                    'is_final' => true,
                ],
            ],
        ];
    }

    // 1. Guest
    public function test_guest_redirected_to_login()
    {
        $this->get('/settings/approval-configurations')->assertRedirect('/login');
        $this->get('/settings/approval-configurations/create')->assertRedirect('/login');
        $this->get("/settings/approval-configurations/{$this->workflow->id}/edit")->assertRedirect('/login');
        $this->get("/settings/approval-configurations/{$this->workflow->id}")->assertRedirect('/login');
        $this->post('/settings/approval-configurations', $this->validPayload())->assertRedirect('/login');
        $this->patch("/settings/approval-configurations/{$this->workflow->id}", $this->validPayload())->assertRedirect('/login');
    }

    // 2. View Only User
    public function test_view_only_user_can_view_index_and_show()
    {
        $this->actingAs($this->viewOnlyUser)
            ->get('/settings/approval-configurations')
            ->assertOk()
            ->assertViewIs('settings.approval-configurations.index');

        $this->actingAs($this->viewOnlyUser)
            ->get("/settings/approval-configurations/{$this->workflow->id}")
            ->assertOk()
            ->assertViewIs('settings.approval-configurations.show');
    }

    public function test_view_only_user_cannot_access_mutations()
    {
        $this->actingAs($this->viewOnlyUser)
            ->get('/settings/approval-configurations/create')
            ->assertForbidden();

        $this->actingAs($this->viewOnlyUser)
            ->get("/settings/approval-configurations/{$this->workflow->id}/edit")
            ->assertForbidden();

        $this->actingAs($this->viewOnlyUser)
            ->post('/settings/approval-configurations', $this->validPayload())
            ->assertForbidden();

        $this->actingAs($this->viewOnlyUser)
            ->patch("/settings/approval-configurations/{$this->workflow->id}", $this->validPayload())
            ->assertForbidden();
    }

    // 3. Manage Only User (Manage does not automatically grant View based on Block 5 rules)
    public function test_manage_only_user_does_not_gain_view()
    {
        $this->actingAs($this->authorizedUser) // Has Manage but not View
            ->get('/settings/approval-configurations')
            ->assertForbidden();

        $this->actingAs($this->authorizedUser)
            ->get("/settings/approval-configurations/{$this->workflow->id}")
            ->assertForbidden();
    }

    // Since we need view to test UI, let's create a View+Manage user
    private function getFullAccessUser(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        UserSystemCapabilityGrant::create([
            'user_id' => $user->id,
            'capability' => UserCapability::APPROVAL_CONFIGURATION_MANAGE,
            'is_active' => true,
        ]);
        UserSystemCapabilityGrant::create([
            'user_id' => $user->id,
            'capability' => UserCapability::APPROVAL_CONFIGURATION_VIEW,
            'is_active' => true,
        ]);

        return $user;
    }

    // 4. View + Manage User
    public function test_full_access_user_can_access_ui()
    {
        $user = $this->getFullAccessUser();

        $this->actingAs($user)
            ->get('/settings/approval-configurations/create')
            ->assertOk()
            ->assertViewIs('settings.approval-configurations.create');

        $this->actingAs($user)
            ->get("/settings/approval-configurations/{$this->workflow->id}/edit")
            ->assertOk()
            ->assertViewIs('settings.approval-configurations.edit');
    }

    // 6. Passive User/Grant
    public function test_passive_user_or_grant_cannot_access()
    {
        $this->actingAs($this->inactiveUser)
            ->get('/settings/approval-configurations/create')
            ->assertForbidden();
    }

    // 7. Route Order / 8. IDOR
    public function test_route_order_and_idor()
    {
        $user = $this->getFullAccessUser();

        // 404 for authorized user but non-existing ID
        $this->actingAs($user)->get('/settings/approval-configurations/99999/edit')->assertNotFound();

        // 403 for unauthorized user and existing ID
        $this->actingAs($this->unauthorizedUser)->get("/settings/approval-configurations/{$this->workflow->id}/edit")->assertForbidden();

        // 403 for unauthorized user and non-existing ID
        $this->actingAs($this->unauthorizedUser)->get('/settings/approval-configurations/99999/edit')->assertForbidden();

        // Ensure /create does not conflict with /{id}
        $this->actingAs($user)->get('/settings/approval-configurations/create')->assertOk();
    }

    public function test_published_workflow_cannot_be_edited()
    {
        $user = $this->getFullAccessUser();
        $this->workflow->update(['published_at' => now(), 'is_active' => true]);

        $this->actingAs($user)
            ->get("/settings/approval-configurations/{$this->workflow->id}/edit")
            ->assertRedirect("/settings/approval-configurations/{$this->workflow->id}");
    }

    // 9. Unauthorized + Invalid Payload -> 403 (Not 422)
    public function test_unauthorized_with_invalid_payload_returns_403()
    {
        $this->actingAs($this->unauthorizedUser)
            ->post('/settings/approval-configurations', []) // Empty payload
            ->assertForbidden();
    }

    // 11. Wrong HTTP Method
    public function test_wrong_http_method_yields_405()
    {
        $user = $this->getFullAccessUser();
        $this->actingAs($user)
            ->put("/settings/approval-configurations/{$this->workflow->id}", $this->validPayload())
            ->assertStatus(405);
    }

    // 14. DOM Checks (No actor_id)
    public function test_dom_does_not_contain_prohibited_fields()
    {
        $user = $this->getFullAccessUser();
        
        $response = $this->actingAs($user)->get('/settings/approval-configurations/create');
        
        $response->assertOk();
        $response->assertDontSee('actor_user_id');
        $response->assertDontSee('user_id');
        $response->assertDontSee('granted_by_user_id');
        // Not checking 'role', 'capability', 'version' as they might naturally occur in text/urls,
        // but let's check for specific form inputs
        $response->assertDontSee('name="actor_user_id"', false);
        $response->assertDontSee('name="user_id"', false);
    }

    public function test_stage_editor_does_not_use_prohibited_dom_injection_apis()
    {
        $user = $this->getFullAccessUser();
        
        $response = $this->actingAs($user)->get('/settings/approval-configurations/create');
        
        $response->assertOk();
        
        // Assert that we don't use innerHTML or insertAdjacentHTML to build the DOM, as per security plan
        $response->assertDontSee('innerHTML', false);
        $response->assertDontSee('insertAdjacentHTML', false);
        $response->assertDontSee('document.write', false);
        $response->assertDontSee('outerHTML', false);
    }

    // 16. JSON Read/Mutation Contract Regression
    public function test_json_requests_still_return_json()
    {
        $user = $this->getFullAccessUser();

        $this->actingAs($user)
            ->getJson('/settings/approval-configurations')
            ->assertJsonStructure(['data', 'meta']);

        $this->actingAs($user)
            ->postJson('/settings/approval-configurations', $this->validPayload())
            ->assertCreated()
            ->assertJsonStructure(['data']);
    }

    // 17. Validation Error & Old Input
    public function test_validation_failure_returns_to_form_with_errors()
    {
        $user = $this->getFullAccessUser();

        $response = $this->actingAs($user)
            ->from('/settings/approval-configurations/create')
            ->post('/settings/approval-configurations', ['code' => '']); // Invalid

        $response->assertRedirect('/settings/approval-configurations/create');
        $response->assertSessionHasErrors(['code']);
    }

    // 19. Successful Create/Update Redirects
    public function test_successful_create_redirects_to_show_with_flash()
    {
        $user = $this->getFullAccessUser();

        $response = $this->actingAs($user)
            ->post('/settings/approval-configurations', $this->validPayload());

        $workflow = ApprovalWorkflow::where('code', 'WF_NEW_UI')->first();
        $this->assertNotNull($workflow);

        $response->assertRedirect("/settings/approval-configurations/{$workflow->id}");
        $response->assertSessionHas('success');
    }

    public function test_successful_update_redirects_with_flash()
    {
        $user = $this->getFullAccessUser();

        $payload = $this->validUpdatePayload();

        $response = $this->actingAs($user)
            ->patch("/settings/approval-configurations/{$this->workflow->id}", $payload);

        $response->assertRedirect("/settings/approval-configurations/{$this->workflow->id}");
        $response->assertSessionHas('success');
    }

    public function test_successful_publish_redirects_with_flash()
    {
        $user = $this->getFullAccessUser();

        $response = $this->actingAs($user)
            ->post("/settings/approval-configurations/{$this->workflow->id}/publish");

        $response->assertRedirect("/settings/approval-configurations/{$this->workflow->id}");
        $response->assertSessionHas('success');
    }

    public function test_successful_set_default_redirects_with_flash()
    {
        $user = $this->getFullAccessUser();
        $this->workflow->update(['published_at' => now(), 'is_active' => true]);

        $response = $this->actingAs($user)
            ->post("/settings/approval-configurations/{$this->workflow->id}/default");

        $response->assertRedirect("/settings/approval-configurations/{$this->workflow->id}");
        $response->assertSessionHas('success');
    }

    public function test_successful_deactivate_redirects_with_flash()
    {
        $user = $this->getFullAccessUser();

        $response = $this->actingAs($user)
            ->post("/settings/approval-configurations/{$this->workflow->id}/deactivate");

        $response->assertRedirect('/settings/approval-configurations');
        $response->assertSessionHas('success');
    }

    // 18. Domain exception flash canary
    public function test_domain_exception_caught_safely_for_html()
    {
        $user = $this->getFullAccessUser();

        // Attempt to publish a workflow without any stages (throws DomainException)
        ApprovalStage::where('approval_workflow_id', $this->workflow->id)->delete();

        $response = $this->actingAs($user)
            ->from("/settings/approval-configurations/{$this->workflow->id}")
            ->post("/settings/approval-configurations/{$this->workflow->id}/publish");

        $response->assertRedirect("/settings/approval-configurations/{$this->workflow->id}");
        $response->assertSessionHas('error');
        // Ensure raw message is NOT leaked. The domain exception message might be "Cannot publish workflow without any active stages."
        $this->assertStringNotContainsString('active stages', session('error'));
    }
}
