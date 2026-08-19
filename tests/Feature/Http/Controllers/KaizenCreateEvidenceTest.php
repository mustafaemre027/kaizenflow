<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\KaizenAttachmentContext;
use App\Models\Category;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\KaizenAttachment;
use App\Models\User;
use App\Services\Kaizens\KaizenAttachmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KaizenCreateEvidenceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Department $department;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->department = Department::factory()->create(['is_active' => true]);
        $this->user = User::factory()->create(['is_active' => true, 'department_id' => $this->department->id]);
        $this->category = Category::factory()->create(['is_active' => true]);
    }

    public function test_zero_file_compatibility(): void
    {
        $payload = [
            'category_id' => $this->category->id,
            'title' => 'Zero file compatibility test',
            'current_situation' => 'Current situation details',
            'proposed_situation' => 'Proposed situation details',
            'expected_benefit' => 'Expected benefit details',
        ];

        $response = $this->actingAs($this->user)->post(route('kaizens.store'), $payload);

        $kaizen = Kaizen::where('title', 'Zero file compatibility test')->first();
        $this->assertNotNull($kaizen);

        $response->assertRedirect(route('kaizens.show', $kaizen));

        $this->assertEquals(0, KaizenAttachment::count());
    }

    public function test_store_with_current_situation_images_only(): void
    {
        $file1 = UploadedFile::fake()->create('current1.jpg', 100, 'image/jpeg');
        $file2 = UploadedFile::fake()->create('current2.jpg', 100, 'image/jpeg');

        $payload = [
            'category_id' => $this->category->id,
            'title' => 'Current situation only test',
            'current_situation' => 'Current situation details',
            'proposed_situation' => 'Proposed situation details',
            'expected_benefit' => 'Expected benefit details',
            'current_situation_images' => [$file1, $file2],
        ];

        $response = $this->actingAs($this->user)->post(route('kaizens.store'), $payload);

        $kaizen = Kaizen::where('title', 'Current situation only test')->firstOrFail();

        $response->assertRedirect(route('kaizens.show', $kaizen));

        $this->assertCount(2, $kaizen->attachments);

        foreach ($kaizen->attachments as $attachment) {
            $this->assertEquals(KaizenAttachmentContext::CURRENT_SITUATION->value, $attachment->context->value);
            $this->assertEquals('local', $attachment->storage_disk);
            $this->assertEquals($this->user->id, $attachment->uploaded_by_user_id);
            Storage::disk('local')->assertExists($attachment->storage_path);
        }
    }

    public function test_store_with_both_contexts_and_metadata_validation(): void
    {
        $current1 = UploadedFile::fake()->create('current1.png', 100, 'image/png');
        $proposed1 = UploadedFile::fake()->create('proposed1.webp', 100, 'image/webp');
        $proposed2 = UploadedFile::fake()->create('proposed2.jpg', 100, 'image/jpeg');

        $payload = [
            'category_id' => $this->category->id,
            'title' => 'Both contexts test',
            'current_situation' => 'Current situation details',
            'proposed_situation' => 'Proposed situation details',
            'expected_benefit' => 'Expected benefit details',
            'current_situation_images' => [$current1],
            'proposed_situation_images' => [$proposed1, $proposed2],
        ];

        $response = $this->actingAs($this->user)->post(route('kaizens.store'), $payload);

        $kaizen = Kaizen::where('title', 'Both contexts test')->firstOrFail();

        $this->assertCount(3, $kaizen->attachments);

        $currentAttachments = $kaizen->attachments()->where('context', KaizenAttachmentContext::CURRENT_SITUATION)->get();
        $this->assertCount(1, $currentAttachments);
        $this->assertEquals('current1.png', $currentAttachments->first()->original_name);
        $this->assertEquals('image/png', $currentAttachments->first()->mime_type);
        $this->assertEquals(102400, $currentAttachments->first()->size_bytes); // 100KB * 1024

        $proposedAttachments = $kaizen->attachments()->where('context', KaizenAttachmentContext::PROPOSED_SITUATION)->get();
        $this->assertCount(2, $proposedAttachments);

        foreach ($kaizen->attachments as $attachment) {
            Storage::disk('local')->assertExists($attachment->storage_path);
        }
    }

    public function test_max_count_validation(): void
    {
        $maxCount = config('kaizen.attachments.max_images_per_context', 8);
        $files = [];

        for ($i = 0; $i <= $maxCount; $i++) {
            $files[] = UploadedFile::fake()->create("file{$i}.jpg", 100, 'image/jpeg');
        }

        $payload = [
            'category_id' => $this->category->id,
            'title' => 'Max count test',
            'current_situation' => 'Current situation details',
            'proposed_situation' => 'Proposed situation details',
            'expected_benefit' => 'Expected benefit details',
            'current_situation_images' => $files,
        ];

        $response = $this->actingAs($this->user)->post(route('kaizens.store'), $payload);

        $response->assertSessionHasErrors('current_situation_images');
        $this->assertEquals(0, Kaizen::count());
        $this->assertEquals(0, KaizenAttachment::count());
    }

    public function test_oversize_validation(): void
    {
        $maxKb = config('kaizen.attachments.max_image_kb', 8192);
        $file = UploadedFile::fake()->create('oversize.jpg', $maxKb + 1, 'image/jpeg');

        $payload = [
            'category_id' => $this->category->id,
            'title' => 'Oversize test',
            'current_situation' => 'Current situation details',
            'proposed_situation' => 'Proposed situation details',
            'expected_benefit' => 'Expected benefit details',
            'current_situation_images' => [$file],
        ];

        $response = $this->actingAs($this->user)->post(route('kaizens.store'), $payload);

        $response->assertSessionHasErrors('current_situation_images.0');
        $this->assertEquals(0, Kaizen::count());
    }

    public function test_mime_validation_rejects_non_image(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $payload = [
            'category_id' => $this->category->id,
            'title' => 'Invalid mime test',
            'current_situation' => 'Current situation details',
            'proposed_situation' => 'Proposed situation details',
            'expected_benefit' => 'Expected benefit details',
            'current_situation_images' => [$file],
        ];

        $response = $this->actingAs($this->user)->post(route('kaizens.store'), $payload);

        $response->assertSessionHasErrors('current_situation_images.0');
        $this->assertEquals(0, Kaizen::count());
    }

    public function test_svg_security_rejection(): void
    {
        $file = UploadedFile::fake()->create('vector.svg', 100, 'image/svg+xml');

        $payload = [
            'category_id' => $this->category->id,
            'title' => 'SVG rejection test',
            'current_situation' => 'Current situation details',
            'proposed_situation' => 'Proposed situation details',
            'expected_benefit' => 'Expected benefit details',
            'current_situation_images' => [$file],
        ];

        $response = $this->actingAs($this->user)->post(route('kaizens.store'), $payload);

        $response->assertSessionHasErrors('current_situation_images.0');
        $this->assertEquals(0, Kaizen::count());
    }

    public function test_misleading_extension_rejection(): void
    {
        // A file that claims to be a jpeg but is really a text file under the hood.
        // Actually, UploadedFile::fake() with a text MIME type but .jpg extension:
        $file = UploadedFile::fake()->create('photo.jpg', 100, 'text/plain');

        $payload = [
            'category_id' => $this->category->id,
            'title' => 'Misleading extension test',
            'current_situation' => 'Current situation details',
            'proposed_situation' => 'Proposed situation details',
            'expected_benefit' => 'Expected benefit details',
            'current_situation_images' => [$file],
        ];

        $response = $this->actingAs($this->user)->post(route('kaizens.store'), $payload);

        $response->assertSessionHasErrors('current_situation_images.0');
        $this->assertEquals(0, Kaizen::count());
    }

    public function test_text_required_regression_even_with_images(): void
    {
        $file = UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg');

        $payload = [
            'category_id' => $this->category->id,
            'title' => 'Text required test',
            'current_situation' => '', // Empty!
            'proposed_situation' => 'Proposed situation details',
            'expected_benefit' => 'Expected benefit details',
            'current_situation_images' => [$file],
        ];

        $response = $this->actingAs($this->user)->post(route('kaizens.store'), $payload);

        $response->assertSessionHasErrors('current_situation');
        $this->assertEquals(0, Kaizen::count());
    }

    public function test_storage_failure_rollback_integration(): void
    {
        $this->withoutExceptionHandling();

        // Mock the service to throw an exception during storeMany
        $this->mock(KaizenAttachmentService::class, function ($mock) {
            $mock->shouldReceive('storeMany')->andThrow(new \Exception('Storage simulated failure'));
        });

        $file = UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg');

        $payload = [
            'category_id' => $this->category->id,
            'title' => 'Storage failure test',
            'current_situation' => 'Current situation details',
            'proposed_situation' => 'Proposed situation details',
            'expected_benefit' => 'Expected benefit details',
            'current_situation_images' => [$file],
        ];

        try {
            $this->actingAs($this->user)->post(route('kaizens.store'), $payload);
            $this->fail('Expected exception was not thrown.');
        } catch (\Exception $e) {
            $this->assertEquals('Storage simulated failure', $e->getMessage());
        }

        // Assert atomicity
        $this->assertEquals(0, Kaizen::count());
        $this->assertEquals(0, KaizenAttachment::count());
    }
}
