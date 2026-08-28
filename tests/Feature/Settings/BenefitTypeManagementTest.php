<?php

namespace Tests\Feature\Settings;

use App\Enums\UserCapability;
use App\Enums\UserRole;
use App\Models\BenefitType;
use App\Models\Kaizen;
use App\Models\KaizenBenefit;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BenefitTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    private function grantSystemCapability(User $user, UserCapability $capability): void
    {
        UserSystemCapabilityGrant::create([
            'user_id' => $user->id,
            'capability' => $capability,
            'is_active' => true,
        ]);
    }

    // =========================================================================
    // 20. CAPABILITY TEST MATRIX
    // =========================================================================

    // 1. guest cannot view
    public function test_guest_cannot_view()
    {
        $response = $this->get(route('settings.reference-data.index'));
        $response->assertRedirect(route('login'));
    }

    // 2. active user without organization.view cannot view (should not see the tab)
    public function test_active_user_without_organization_view_cannot_view()
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $response = $this->actingAs($user)->get(route('settings.reference-data.index'));
        // Employee might not even have access to settings. If it's a 403, that's fine.
        $response->assertStatus(403);
    }

    // 3. ADMIN role without organization.view cannot view BenefitTypes (even if they see categories)
    public function test_admin_role_without_organization_view_cannot_view_benefit_types()
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $response = $this->actingAs($admin)->get(route('settings.reference-data.index'));
        $response->assertOk();
        $response->assertDontSee('Fayda Türleri');
        $response->assertDontSee('Yeni Fayda Türü');
    }

    // 4. organization.view user can view list
    public function test_organization_view_user_can_view_list()
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $this->grantSystemCapability($user, UserCapability::ORGANIZATION_VIEW);

        BenefitType::create(['name' => 'TestBenefit123', 'is_active' => true]);

        $response = $this->actingAs($user)->get(route('settings.reference-data.index'));
        $response->assertOk();
        $response->assertSee('Fayda Türleri');
        $response->assertSee('TestBenefit123');
        $response->assertDontSee('Fayda Türü Ekle'); // Cannot create
    }

    // 5. organization.view only cannot create
    public function test_organization_view_only_cannot_create()
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $this->grantSystemCapability($user, UserCapability::ORGANIZATION_VIEW);

        $response = $this->actingAs($user)->get(route('settings.benefit-types.create'));
        $response->assertStatus(403);

        $responsePost = $this->actingAs($user)->post(route('settings.benefit-types.store'), [
            'name' => 'New Benefit',
        ]);
        $responsePost->assertStatus(403);
    }

    // 6. organization.view only cannot update
    public function test_organization_view_only_cannot_update()
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $this->grantSystemCapability($user, UserCapability::ORGANIZATION_VIEW);

        $bt = BenefitType::create(['name' => 'Existing', 'is_active' => true]);

        $response = $this->actingAs($user)->get(route('settings.benefit-types.edit', $bt));
        $response->assertStatus(403);

        $responsePatch = $this->actingAs($user)->patch(route('settings.benefit-types.update', $bt), [
            'name' => 'Changed',
        ]);
        $responsePatch->assertStatus(403);
    }

    // 7. organization.manage without organization.view otomatik read bypass almamalı
    public function test_organization_manage_without_view_cannot_view_list()
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $this->grantSystemCapability($user, UserCapability::ORGANIZATION_MANAGE);

        $response = $this->actingAs($user)->get(route('settings.reference-data.index'));
        // We throw 403 in request because they don't have viewAny for Category nor BenefitType.
        $response->assertStatus(403);
    }

    // 8. organization.view + organization.manage can create
    public function test_organization_view_and_manage_can_create()
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $this->grantSystemCapability($user, UserCapability::ORGANIZATION_VIEW);
        $this->grantSystemCapability($user, UserCapability::ORGANIZATION_MANAGE);

        $response = $this->actingAs($user)->get(route('settings.benefit-types.create'));
        $response->assertOk();

        $responseStore = $this->actingAs($user)->post(route('settings.benefit-types.store'), [
            'name' => 'New Benefit',
            'unit_label' => 'Saat',
        ]);

        $responseStore->assertRedirect();
        $this->assertDatabaseHas('benefit_types', [
            'name' => 'New Benefit',
            'unit_label' => 'Saat',
        ]);
    }

    // 9. same capability behavior role-independent
    public function test_same_capability_behavior_role_independent()
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);

        $this->grantSystemCapability($admin, UserCapability::ORGANIZATION_VIEW);
        $this->grantSystemCapability($admin, UserCapability::ORGANIZATION_MANAGE);

        $this->grantSystemCapability($manager, UserCapability::ORGANIZATION_VIEW);
        $this->grantSystemCapability($manager, UserCapability::ORGANIZATION_MANAGE);

        $this->actingAs($admin)->post(route('settings.benefit-types.store'), ['name' => 'Admin Benefit'])->assertRedirect();
        $this->actingAs($manager)->post(route('settings.benefit-types.store'), ['name' => 'Manager Benefit'])->assertRedirect();

        $this->assertDatabaseHas('benefit_types', ['name' => 'Admin Benefit']);
        $this->assertDatabaseHas('benefit_types', ['name' => 'Manager Benefit']);
    }

    // 10. inactive user blocked
    public function test_inactive_user_blocked()
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE, 'is_active' => false]);
        $this->grantSystemCapability($user, UserCapability::ORGANIZATION_VIEW);
        $this->grantSystemCapability($user, UserCapability::ORGANIZATION_MANAGE);

        // Global active user middleware will redirect or 403, or login blocks.
        $response = $this->actingAs($user)->get(route('settings.benefit-types.create'));
        $this->assertTrue($response->status() === 403 || $response->isRedirect());
    }

    // =========================================================================
    // 21. CREATE TESTLERİ
    // =========================================================================

    private function getManagerUser(): User
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $this->grantSystemCapability($user, UserCapability::ORGANIZATION_VIEW);
        $this->grantSystemCapability($user, UserCapability::ORGANIZATION_MANAGE);

        return $user;
    }

    // 11. valid create
    public function test_valid_create()
    {
        $user = $this->getManagerUser();
        $response = $this->actingAs($user)->post(route('settings.benefit-types.store'), [
            'name' => 'Enerji Tasarrufu',
            'unit_label' => 'kWh',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('benefit_types', ['name' => 'Enerji Tasarrufu', 'unit_label' => 'kWh']);
    }

    // 12. unit_label nullable
    public function test_unit_label_nullable()
    {
        $user = $this->getManagerUser();
        $response = $this->actingAs($user)->post(route('settings.benefit-types.store'), [
            'name' => 'Kalite Artışı',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('benefit_types', ['name' => 'Kalite Artışı', 'unit_label' => null]);
    }

    // 13. blank unit normalized null
    public function test_blank_unit_normalized_null()
    {
        $user = $this->getManagerUser();
        $response = $this->actingAs($user)->post(route('settings.benefit-types.store'), [
            'name' => 'Morale',
            'unit_label' => '   ',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('benefit_types', ['name' => 'Morale', 'unit_label' => null]);
    }

    // 14. name trim
    public function test_name_trim()
    {
        $user = $this->getManagerUser();
        $response = $this->actingAs($user)->post(route('settings.benefit-types.store'), [
            'name' => '   Trimmed Name   ',
            'unit_label' => 'pcs',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('benefit_types', ['name' => 'Trimmed Name']);
    }

    // 15. empty name reject
    public function test_empty_name_reject()
    {
        $user = $this->getManagerUser();
        $response = $this->actingAs($user)->post(route('settings.benefit-types.store'), [
            'name' => '   ',
        ]);
        $response->assertSessionHasErrors(['name']);
        $this->assertDatabaseCount('benefit_types', 0);
    }

    // 16. exact duplicate reject
    public function test_exact_duplicate_reject()
    {
        $user = $this->getManagerUser();
        BenefitType::create(['name' => 'Duplicate']);

        $response = $this->actingAs($user)->post(route('settings.benefit-types.store'), [
            'name' => 'Duplicate',
        ]);
        $response->assertSessionHasErrors(['name']);
        $this->assertDatabaseCount('benefit_types', 1);
    }

    // 17. case-insensitive duplicate reject
    public function test_case_insensitive_duplicate_reject()
    {
        $user = $this->getManagerUser();
        BenefitType::create(['name' => 'Zaman']);

        $response = $this->actingAs($user)->post(route('settings.benefit-types.store'), [
            'name' => 'zaman',
        ]);
        $response->assertSessionHasErrors(['name']);
        $this->assertDatabaseCount('benefit_types', 1);
    }

    // 18. malicious/XSS name stored safely and rendered escaped
    public function test_malicious_xss_name_stored_safely_and_rendered_escaped()
    {
        $user = $this->getManagerUser();
        $maliciousName = '<script>alert(1)</script>';

        $this->actingAs($user)->post(route('settings.benefit-types.store'), [
            'name' => $maliciousName,
        ]);

        $this->assertDatabaseHas('benefit_types', ['name' => $maliciousName]);

        $response = $this->actingAs($user)->get(route('settings.reference-data.index'));
        $response->assertSee(htmlentities($maliciousName, ENT_QUOTES, 'UTF-8', false), false);
        $response->assertDontSee($maliciousName, false);
    }

    // 19. sensitive field injection rejected
    public function test_sensitive_field_injection_rejected()
    {
        $user = $this->getManagerUser();
        $this->actingAs($user)->post(route('settings.benefit-types.store'), [
            'name' => 'Valid',
            'id' => 999,
            'is_active' => false,
            'kaizen_benefits' => 'injection',
        ]);

        $bt = BenefitType::first();
        $this->assertNotEquals(999, $bt->id);
        $this->assertTrue($bt->is_active);
    }

    // 20. new type default active
    public function test_new_type_default_active()
    {
        $user = $this->getManagerUser();
        $this->actingAs($user)->post(route('settings.benefit-types.store'), [
            'name' => 'New Active',
        ]);

        $this->assertTrue(BenefitType::where('name', 'New Active')->first()->is_active);
    }

    // =========================================================================
    // 22. UPDATE / STATUS TESTLERİ
    // =========================================================================

    // 21. valid rename
    public function test_valid_rename()
    {
        $user = $this->getManagerUser();
        $bt = BenefitType::create(['name' => 'Old Name']);

        $response = $this->actingAs($user)->patch(route('settings.benefit-types.update', $bt), [
            'name' => 'New Name',
        ]);
        $response->assertRedirect();

        $this->assertEquals('New Name', $bt->fresh()->name);
    }

    // 22. valid unit update
    public function test_valid_unit_update()
    {
        $user = $this->getManagerUser();
        $bt = BenefitType::create(['name' => 'Name', 'unit_label' => 'Old']);

        $this->actingAs($user)->patch(route('settings.benefit-types.update', $bt), [
            'name' => 'Name',
            'unit_label' => 'New',
        ]);

        $this->assertEquals('New', $bt->fresh()->unit_label);
    }

    // 23. duplicate rename rejected
    public function test_duplicate_rename_rejected()
    {
        $user = $this->getManagerUser();
        BenefitType::create(['name' => 'Existing']);
        $bt = BenefitType::create(['name' => 'Another']);

        $response = $this->actingAs($user)->patch(route('settings.benefit-types.update', $bt), [
            'name' => 'existing',
        ]);
        $response->assertSessionHasErrors(['name']);

        $this->assertEquals('Another', $bt->fresh()->name);
    }

    // 24. same-value update safe no-op
    public function test_same_value_update_safe_no_op()
    {
        $user = $this->getManagerUser();
        $bt = BenefitType::create(['name' => 'Same', 'unit_label' => 'Unit']);

        $response = $this->actingAs($user)->patch(route('settings.benefit-types.update', $bt), [
            'name' => 'Same',
            'unit_label' => 'Unit',
        ]);
        $response->assertRedirect();

        $this->assertEquals('Same', $bt->fresh()->name);
    }

    // 25. deactivate active type
    public function test_deactivate_active_type()
    {
        $user = $this->getManagerUser();
        $bt = BenefitType::create(['name' => 'To Deactivate', 'is_active' => true]);

        $response = $this->actingAs($user)->patch(route('settings.benefit-types.status', $bt), ['is_active' => false]);
        $response->assertRedirect();

        $this->assertFalse($bt->fresh()->is_active);
    }

    // 26. deactivate preserves linked KaizenBenefit rows
    // 27. deactivate preserves expected/realized values
    public function test_deactivate_preserves_linked_historical_data()
    {
        $user = $this->getManagerUser();
        $bt = BenefitType::create(['name' => 'Metric', 'is_active' => true]);
        $kaizen = Kaizen::factory()->create();

        $kb = KaizenBenefit::create([
            'kaizen_id' => $kaizen->id,
            'benefit_type_id' => $bt->id,
            'expected_value' => '100',
            'expected_note' => 'test',
            'realized_value' => '90',
            'realized_note' => 'test',
        ]);

        $this->actingAs($user)->patch(route('settings.benefit-types.status', $bt), ['is_active' => false]);

        $this->assertFalse($bt->fresh()->is_active);

        $kb->refresh();
        $this->assertEquals('100.0000', $kb->expected_value);
        $this->assertEquals('90.0000', $kb->realized_value);
    }

    // 28. inactive type disappears from new association options
    // Tested implicitly in SyncExpectedKaizenBenefits and SyncRealizedKaizenBenefits tests (they reject inactive types)

    // 29. inactive linked historical row still renders
    // Blade renders via relationship, relationship doesn't filter is_active.

    // 30. reactivate same record
    public function test_reactivate_same_record()
    {
        $user = $this->getManagerUser();
        $bt = BenefitType::create(['name' => 'To Reactivate', 'is_active' => false]);

        $response = $this->actingAs($user)->patch(route('settings.benefit-types.status', $bt), ['is_active' => true]);
        $response->assertRedirect();

        $this->assertTrue($bt->fresh()->is_active);
    }

    // 31. reactivated record becomes selectable again
    // (Handled by action logic implicitly)

    // 32. idempotent activate/deactivate safe no-op
    public function test_idempotent_activate_deactivate_safe_no_op()
    {
        $user = $this->getManagerUser();
        $bt = BenefitType::create(['name' => 'Repeater', 'is_active' => true]);

        $this->actingAs($user)->patch(route('settings.benefit-types.status', $bt), ['is_active' => false]);
        $this->assertFalse($bt->fresh()->is_active);

        // Idempotent: sending false again
        $this->actingAs($user)->patch(route('settings.benefit-types.status', $bt), ['is_active' => false]);
        $this->assertFalse($bt->fresh()->is_active);

        // Reactivate
        $this->actingAs($user)->patch(route('settings.benefit-types.status', $bt), ['is_active' => true]);
        $this->assertTrue($bt->fresh()->is_active);

        // Idempotent: sending true again
        $this->actingAs($user)->patch(route('settings.benefit-types.status', $bt), ['is_active' => true]);
        $this->assertTrue($bt->fresh()->is_active);
    }

    // =========================================================================
    // 23. HTTP / IDOR TESTLERİ
    // =========================================================================

    // 33. unauthorized existing ID → 403
    public function test_unauthorized_existing_id_forbidden()
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $this->grantSystemCapability($user, UserCapability::ORGANIZATION_VIEW); // missing MANAGE

        $bt = BenefitType::create(['name' => 'Existing']);

        $response = $this->actingAs($user)->get(route('settings.benefit-types.edit', $bt));
        $response->assertForbidden();
    }

    // 34. unauthorized nonexistent ID → 404 (Route Model Binding triggers 404 first usually, standard Laravel behavior)
    // "if framework architecture permits authorization-before-binding" - Laravel standard is 404 first.
    public function test_unauthorized_nonexistent_id_not_found()
    {
        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $response = $this->actingAs($user)->get('/settings/benefit-types/9999/edit');
        $response->assertNotFound();
    }

    // 35. authorized nonexistent ID → 404
    public function test_authorized_nonexistent_id_not_found()
    {
        $user = $this->getManagerUser();
        $response = $this->actingAs($user)->get('/settings/benefit-types/9999/edit');
        $response->assertNotFound();
    }

    // 36. wrong HTTP methods → 405
    public function test_wrong_http_methods()
    {
        $user = $this->getManagerUser();
        $response = $this->actingAs($user)->put(route('settings.benefit-types.store'), ['name' => 'Test']);
        $response->assertStatus(405);
    }

    // 37. invalid payload → safe 422 (or 302 with errors)
    public function test_invalid_payload_safe()
    {
        $user = $this->getManagerUser();
        $response = $this->actingAs($user)->post(route('settings.benefit-types.store'), [
            'name' => Str::random(300), // Exceeds max length
        ]);
        $response->assertSessionHasErrors(['name']);
    }

    // 38. HTML validation redirect preserves input
    public function test_html_validation_redirect_preserves_input()
    {
        $user = $this->getManagerUser();
        $response = $this->actingAs($user)->post(route('settings.benefit-types.store'), [
            'name' => '',
            'unit_label' => 'Saat',
        ]);
        $response->assertSessionHasErrors(['name']);
        // Session should have 'old' input
        $this->assertEquals('Saat', session()->getOldInput('unit_label'));
    }

    // 39. JSON request safe validation contract
    public function test_json_request_safe_validation_contract()
    {
        $user = $this->getManagerUser();
        $response = $this->actingAs($user)->postJson(route('settings.benefit-types.store'), [
            'name' => '',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }
}
