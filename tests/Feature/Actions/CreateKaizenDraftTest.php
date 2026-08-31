<?php

namespace Tests\Feature\Actions;

use App\Actions\Kaizens\CreateKaizenDraft;
use App\Enums\KaizenPriority;
use App\Enums\KaizenStatus;
use App\Models\Category;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\User;
use App\Services\KaizenCodeGenerator;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateKaizenDraftTest extends TestCase
{
    use RefreshDatabase;

    private CreateKaizenDraft $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = $this->app->make(CreateKaizenDraft::class);
    }

    public function test_active_user_with_department_can_create_draft(): void
    {
        $department = Department::factory()->create(['is_active' => true]);
        $creator = User::factory()->create([
            'is_active' => true,
            'department_id' => $department->id,
        ]);
        $category = Category::factory()->create(['is_active' => true]);

        $attributes = [
            'title' => 'Test Kaizen',
            'current_situation' => 'Current',
            'proposed_situation' => 'Proposed',
            'expected_benefit' => 'Benefit',
            'priority' => KaizenPriority::MEDIUM->value,
            'target_date' => '2026-12-31',
        ];

        $kaizen = $this->action->execute($creator, $category, $attributes);

        $this->assertInstanceOf(Kaizen::class, $kaizen);
        $this->assertDatabaseHas('kaizens', [
            'id' => $kaizen->id,
            'title' => 'Test Kaizen',
            'creator_user_id' => $creator->id,
            'department_id' => $department->id,
            'category_id' => $category->id,
            'status' => KaizenStatus::DRAFT->value,
            'assigned_user_id' => null,
            'actual_result' => null,
            'realized_benefit' => null,
        ]);

        $this->assertMatchesRegularExpression('/^KZN-\d{4}-\d{6,}$/', $kaizen->code);
    }

    public function test_it_generates_different_codes_for_two_drafts(): void
    {
        $department = Department::factory()->create(['is_active' => true]);
        $creator = User::factory()->create([
            'is_active' => true,
            'department_id' => $department->id,
        ]);
        $category = Category::factory()->create(['is_active' => true]);

        $attributes = [
            'title' => 'Test',
            'current_situation' => 'C',
            'proposed_situation' => 'P',
            'expected_benefit' => 'E',
        ];

        $kaizen1 = $this->action->execute($creator, $category, $attributes);
        $kaizen2 = $this->action->execute($creator, $category, $attributes);

        $this->assertNotEquals($kaizen1->code, $kaizen2->code);
    }

    public function test_it_ignores_sensitive_fields_in_attributes(): void
    {
        $department = Department::factory()->create(['is_active' => true]);
        $creator = User::factory()->create([
            'is_active' => true,
            'department_id' => $department->id,
        ]);
        $category = Category::factory()->create(['is_active' => true]);
        $otherUser = User::factory()->create();

        $attributes = [
            'title' => 'Test Kaizen',
            'current_situation' => 'Current',
            'proposed_situation' => 'Proposed',
            'expected_benefit' => 'Benefit',
            // Sensitive fields that should be ignored
            'code' => 'FAKE-CODE',
            'status' => KaizenStatus::APPROVED->value,
            'creator_user_id' => $otherUser->id,
            'assigned_user_id' => $otherUser->id,
            'actual_result' => 'Hacked Result',
            'realized_benefit' => 'Hacked Benefit',
        ];

        $kaizen = $this->action->execute($creator, $category, $attributes);

        $this->assertNotEquals('FAKE-CODE', $kaizen->code);
        $this->assertEquals(KaizenStatus::DRAFT, $kaizen->status);
        $this->assertEquals($creator->id, $kaizen->creator_user_id);
        $this->assertNull($kaizen->assigned_user_id);
        $this->assertNull($kaizen->actual_result);
        $this->assertNull($kaizen->realized_benefit);
    }

    public function test_it_rejects_inactive_creator(): void
    {
        $department = Department::factory()->create(['is_active' => true]);
        $creator = User::factory()->create([
            'is_active' => false,
            'department_id' => $department->id,
        ]);
        $category = Category::factory()->create(['is_active' => true]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Inactive users cannot create a Kaizen.');

        $this->action->execute($creator, $category, []);
    }

    public function test_it_rejects_creator_without_department(): void
    {
        $creator = User::factory()->create([
            'is_active' => true,
            'department_id' => null,
        ]);
        $category = Category::factory()->create(['is_active' => true]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('User must belong to a department to create a Kaizen.');

        $this->action->execute($creator, $category, []);
    }

    public function test_it_rejects_creator_with_inactive_department(): void
    {
        $department = Department::factory()->create(['is_active' => false]);
        $creator = User::factory()->create([
            'is_active' => true,
            'department_id' => $department->id,
        ]);
        $category = Category::factory()->create(['is_active' => true]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('User department must be active to create a Kaizen.');

        $this->action->execute($creator, $category, []);
    }

    public function test_it_rejects_inactive_category(): void
    {
        $department = Department::factory()->create(['is_active' => true]);
        $creator = User::factory()->create([
            'is_active' => true,
            'department_id' => $department->id,
        ]);
        $category = Category::factory()->create(['is_active' => false]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Category must be active to create a Kaizen.');

        $this->action->execute($creator, $category, []);
    }

    public function test_it_rolls_back_transaction_on_code_generation_failure(): void
    {
        $department = Department::factory()->create(['is_active' => true]);
        $creator = User::factory()->create([
            'is_active' => true,
            'department_id' => $department->id,
        ]);
        $category = Category::factory()->create(['is_active' => true]);

        $attributes = [
            'title' => 'Test Rollback',
            'current_situation' => 'C',
            'proposed_situation' => 'P',
            'expected_benefit' => 'E',
        ];

        // Mock KaizenCodeGenerator to throw an exception
        $mockGenerator = $this->createStub(KaizenCodeGenerator::class);
        $mockGenerator->method('generate')->willThrowException(new \RuntimeException('Generator error'));

        $actionWithMock = new CreateKaizenDraft($mockGenerator);

        $initialCount = Kaizen::count();

        try {
            $actionWithMock->execute($creator, $category, $attributes);
        } catch (\RuntimeException $e) {
            $this->assertEquals('Generator error', $e->getMessage());
        }

        // Verify that the record was completely rolled back and no partial Kaizen exists
        $this->assertEquals($initialCount, Kaizen::count());
    }
}
