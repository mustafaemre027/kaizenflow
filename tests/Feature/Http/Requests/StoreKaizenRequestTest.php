<?php

namespace Tests\Feature\Http\Requests;

use App\Enums\KaizenPriority;
use App\Http\Requests\Kaizens\StoreKaizenRequest;
use App\Models\Category;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreKaizenRequestTest extends TestCase
{
    use RefreshDatabase;

    private StoreKaizenRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new StoreKaizenRequest;
    }

    private function validate(array $data): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($data, $this->request->rules());
    }

    public function test_valid_payload_passes(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $validator = $this->validate([
            'category_id' => $category->id,
            'title' => 'Valid Title',
            'current_situation' => 'Current situation is bad.',
            'proposed_situation' => 'Proposed situation is good.',
            'expected_benefit' => 'Expected benefit is high.',
            'priority' => KaizenPriority::MEDIUM->value,
            'target_date' => Carbon::tomorrow()->format('Y-m-d'),
            'current_situation_images' => [UploadedFile::fake()->create('current.jpg', 10, 'image/jpeg')],
            'proposed_situation_images' => [UploadedFile::fake()->create('proposed.jpg', 10, 'image/jpeg')],
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_it_rejects_missing_required_fields(): void
    {
        $validator = $this->validate([]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('category_id'));
        $this->assertTrue($validator->errors()->has('title'));
        $this->assertTrue($validator->errors()->has('current_situation'));
        $this->assertTrue($validator->errors()->has('proposed_situation'));
    }

    public function test_it_allows_missing_expected_benefit(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $validator = $this->validate([
            'category_id' => $category->id,
            'title' => 'Valid Title',
            'current_situation' => 'Current situation is bad.',
            'proposed_situation' => 'Proposed situation is good.',
            // missing expected_benefit
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_it_rejects_inactive_category(): void
    {
        $category = Category::factory()->create(['is_active' => false]);

        $validator = $this->validate([
            'category_id' => $category->id,
            'title' => 'Valid Title',
            'current_situation' => 'Current situation is bad.',
            'proposed_situation' => 'Proposed situation is good.',
            'expected_benefit' => 'Expected benefit is high.',
            'current_situation_images' => [UploadedFile::fake()->create('current.jpg', 10, 'image/jpeg')],
            'proposed_situation_images' => [UploadedFile::fake()->create('proposed.jpg', 10, 'image/jpeg')],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('category_id'));
    }

    public function test_it_rejects_non_existent_category(): void
    {
        $validator = $this->validate([
            'category_id' => 9999,
            'title' => 'Valid Title',
            'current_situation' => 'Current situation is bad.',
            'proposed_situation' => 'Proposed situation is good.',
            'expected_benefit' => 'Expected benefit is high.',
            'current_situation_images' => [UploadedFile::fake()->create('current.jpg', 10, 'image/jpeg')],
            'proposed_situation_images' => [UploadedFile::fake()->create('proposed.jpg', 10, 'image/jpeg')],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('category_id'));
    }

    public function test_it_rejects_short_title(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $validator = $this->validate([
            'category_id' => $category->id,
            'title' => 'Val', // less than 5
            'current_situation' => 'Current situation is bad.',
            'proposed_situation' => 'Proposed situation is good.',
            'expected_benefit' => 'Expected benefit is high.',
            'current_situation_images' => [UploadedFile::fake()->create('current.jpg', 10, 'image/jpeg')],
            'proposed_situation_images' => [UploadedFile::fake()->create('proposed.jpg', 10, 'image/jpeg')],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('title'));
    }

    public function test_it_rejects_long_title(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $validator = $this->validate([
            'category_id' => $category->id,
            'title' => str_repeat('a', 256), // more than 255
            'current_situation' => 'Current situation is bad.',
            'proposed_situation' => 'Proposed situation is good.',
            'expected_benefit' => 'Expected benefit is high.',
            'current_situation_images' => [UploadedFile::fake()->create('current.jpg', 10, 'image/jpeg')],
            'proposed_situation_images' => [UploadedFile::fake()->create('proposed.jpg', 10, 'image/jpeg')],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('title'));
    }

    public function test_it_rejects_short_descriptions(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $validator = $this->validate([
            'category_id' => $category->id,
            'title' => 'Valid Title',
            'current_situation' => 'Short', // less than 10
            'proposed_situation' => 'Short', // less than 10
            'expected_benefit' => 'Short', // less than 10
            'current_situation_images' => [UploadedFile::fake()->create('current.jpg', 10, 'image/jpeg')],
            'proposed_situation_images' => [UploadedFile::fake()->create('proposed.jpg', 10, 'image/jpeg')],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('current_situation'));
        $this->assertTrue($validator->errors()->has('proposed_situation'));
    }

    public function test_it_accepts_valid_priorities_and_rejects_invalid(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $baseData = [
            'category_id' => $category->id,
            'title' => 'Valid Title',
            'current_situation' => 'Current situation is bad.',
            'proposed_situation' => 'Proposed situation is good.',
            'expected_benefit' => 'Expected benefit is high.',
            'current_situation_images' => [UploadedFile::fake()->create('current.jpg', 10, 'image/jpeg')],
            'proposed_situation_images' => [UploadedFile::fake()->create('proposed.jpg', 10, 'image/jpeg')],
        ];

        // Valid
        $validator = $this->validate(array_merge($baseData, ['priority' => KaizenPriority::LOW->value]));
        $this->assertTrue($validator->passes());

        $validator = $this->validate(array_merge($baseData, ['priority' => KaizenPriority::HIGH->value]));
        $this->assertTrue($validator->passes());

        // Invalid
        $validator = $this->validate(array_merge($baseData, ['priority' => 'CRITICAL']));
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('priority'));
    }

    public function test_date_validations(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $baseData = [
            'category_id' => $category->id,
            'title' => 'Valid Title',
            'current_situation' => 'Current situation is bad.',
            'proposed_situation' => 'Proposed situation is good.',
            'expected_benefit' => 'Expected benefit is high.',
            'current_situation_images' => [UploadedFile::fake()->create('current.jpg', 10, 'image/jpeg')],
            'proposed_situation_images' => [UploadedFile::fake()->create('proposed.jpg', 10, 'image/jpeg')],
        ];

        // Today is valid
        $validator = $this->validate(array_merge($baseData, ['target_date' => now()->format('Y-m-d')]));
        $this->assertTrue($validator->passes());

        // Future is valid
        $validator = $this->validate(array_merge($baseData, ['target_date' => now()->addDays(5)->format('Y-m-d')]));
        $this->assertTrue($validator->passes());

        // Past is invalid
        $validator = $this->validate(array_merge($baseData, ['target_date' => now()->subDay()->format('Y-m-d')]));
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('target_date'));
    }

    public function test_it_prohibits_sensitive_fields(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $baseData = [
            'category_id' => $category->id,
            'title' => 'Valid Title',
            'current_situation' => 'Current situation is bad.',
            'proposed_situation' => 'Proposed situation is good.',
            'expected_benefit' => 'Expected benefit is high.',
            'current_situation_images' => [UploadedFile::fake()->create('current.jpg', 10, 'image/jpeg')],
            'proposed_situation_images' => [UploadedFile::fake()->create('proposed.jpg', 10, 'image/jpeg')],
        ];

        $sensitiveFields = [
            'code' => 'KZN-01',
            'creator_user_id' => 1,
            'department_id' => 1,
            'assigned_user_id' => 2,
            'status' => 'APPROVED',
            'actual_result' => 'Result',
            'realized_benefit' => 'Benefit',
            'submitted_at' => now()->format('Y-m-d H:i:s'),
            'approved_at' => now()->format('Y-m-d H:i:s'),
            'started_at' => now()->format('Y-m-d H:i:s'),
            'completed_at' => now()->format('Y-m-d H:i:s'),
            'rejected_at' => now()->format('Y-m-d H:i:s'),
            'created_at' => now()->format('Y-m-d H:i:s'),
            'updated_at' => now()->format('Y-m-d H:i:s'),
        ];

        $validator = $this->validate(array_merge($baseData, $sensitiveFields));

        $this->assertTrue($validator->fails());

        foreach (array_keys($sensitiveFields) as $field) {
            $this->assertTrue($validator->errors()->has($field));
        }
    }

    public function test_authorize_returns_true_for_active_user_with_department(): void
    {
        $department = Department::factory()->create(['is_active' => true]);
        $user = User::factory()->create([
            'is_active' => true,
            'department_id' => $department->id,
        ]);

        $this->request->setUserResolver(fn () => $user);

        $this->assertTrue($this->request->authorize());
    }

    public function test_authorize_returns_false_for_inactive_user(): void
    {
        $department = Department::factory()->create(['is_active' => true]);
        $user = User::factory()->create([
            'is_active' => false,
            'department_id' => $department->id,
        ]);

        $this->request->setUserResolver(fn () => $user);

        $this->assertFalse($this->request->authorize());
    }

    public function test_authorize_returns_false_for_user_without_department(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'department_id' => null,
        ]);

        $this->request->setUserResolver(fn () => $user);

        $this->assertFalse($this->request->authorize());
    }

    public function test_authorize_returns_false_for_user_with_inactive_department(): void
    {
        $department = Department::factory()->create(['is_active' => false]);
        $user = User::factory()->create([
            'is_active' => true,
            'department_id' => $department->id,
        ]);

        $this->request->setUserResolver(fn () => $user);

        $this->assertFalse($this->request->authorize());
    }
}
