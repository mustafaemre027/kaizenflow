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

    public function test_store_with_no_images_is_successful(): void
    {
        $payload = [
            'category_id' => $this->category->id,
            'title' => 'No images test',
            'current_situation' => 'Current situation details',
            'proposed_situation' => 'Proposed situation details',
            'expected_benefit' => 'Expected benefit details',
        ];

        $response = $this->actingAs($this->user)->post(route('kaizens.store'), $payload);

        $kaizen = Kaizen::where('title', 'No images test')->first();
        $this->assertNotNull($kaizen);

        $response->assertRedirect(route('kaizens.show', $kaizen));

        $this->assertEquals(0, KaizenAttachment::count());
    }

    public function test_store_with_only_current_situation_images(): void
    {
        $payload = [
            'category_id' => $this->category->id,
            'title' => 'Only current images test',
            'current_situation' => 'Current situation details',
            'proposed_situation' => 'Proposed situation details',
            'expected_benefit' => 'Expected benefit details',
            'current_situation_images' => [
                UploadedFile::fake()->create('current1.jpg', 100, 'image/jpeg'),
                UploadedFile::fake()->create('current2.jpg', 100, 'image/jpeg'),
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('kaizens.store'), $payload);

        $kaizen = Kaizen::where('title', 'Only current images test')->first();
        $response->assertRedirect(route('kaizens.show', $kaizen));
        $this->assertCount(2, $kaizen->attachments);
    }

    public function test_store_with_only_proposed_situation_images(): void
    {
        $payload = [
            'category_id' => $this->category->id,
            'title' => 'Only proposed images test',
            'current_situation' => 'Current situation details',
            'proposed_situation' => 'Proposed situation details',
            'expected_benefit' => 'Expected benefit details',
            'proposed_situation_images' => [
                UploadedFile::fake()->create('proposed1.jpg', 100, 'image/jpeg'),
                UploadedFile::fake()->create('proposed2.jpg', 100, 'image/jpeg'),
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('kaizens.store'), $payload);

        $kaizen = Kaizen::where('title', 'Only proposed images test')->first();
        $response->assertRedirect(route('kaizens.show', $kaizen));
        $this->assertCount(2, $kaizen->attachments);
    }

    public function test_store_with_single_images_for_both_contexts(): void
    {
        $currentFile = UploadedFile::fake()->create('current.jpg', 100, 'image/jpeg');
        $proposedFile = UploadedFile::fake()->create('proposed.jpg', 100, 'image/jpeg');

        $payload = [
            'category_id' => $this->category->id,
            'title' => 'Single images test',
            'current_situation' => 'Current situation details',
            'proposed_situation' => 'Proposed situation details',
            'expected_benefit' => 'Expected benefit details',
            'current_situation_images' => [$currentFile],
            'proposed_situation_images' => [$proposedFile],
        ];

        $response = $this->actingAs($this->user)->post(route('kaizens.store'), $payload);
        if ($response->status() !== 302) {
            dd($response->json() ?? $response->content());
        }
        $response->assertSessionHasNoErrors();
        $kaizen = Kaizen::where('title', 'Single images test')->firstOrFail();

        $response->assertRedirect(route('kaizens.show', $kaizen));

        $this->assertCount(2, $kaizen->attachments);
    }

    public function test_store_with_multiple_images_for_both_contexts_and_metadata_validation(): void
    {
        $current1 = UploadedFile::fake()->create('current1.png', 100, 'image/png');
        $current2 = UploadedFile::fake()->create('current2.jpg', 100, 'image/jpeg');
        $proposed1 = UploadedFile::fake()->create('proposed1.webp', 100, 'image/webp');
        $proposed2 = UploadedFile::fake()->create('proposed2.jpg', 100, 'image/jpeg');

        $payload = [
            'category_id' => $this->category->id,
            'title' => 'Both contexts test',
            'current_situation' => 'Current situation details',
            'proposed_situation' => 'Proposed situation details',
            'expected_benefit' => 'Expected benefit details',
            'current_situation_images' => [$current1, $current2],
            'proposed_situation_images' => [$proposed1, $proposed2],
        ];

        $response = $this->actingAs($this->user)->post(route('kaizens.store'), $payload);

        $kaizen = Kaizen::where('title', 'Both contexts test')->firstOrFail();

        $this->assertCount(4, $kaizen->attachments);

        $currentAttachments = $kaizen->attachments()->where('context', KaizenAttachmentContext::CURRENT_SITUATION)->get();
        $this->assertCount(2, $currentAttachments);
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
            'proposed_situation_images' => [UploadedFile::fake()->create('proposed.jpg', 100, 'image/jpeg')],
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
            'proposed_situation_images' => [UploadedFile::fake()->create('proposed.jpg', 100, 'image/jpeg')],
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
            'proposed_situation_images' => [UploadedFile::fake()->create('proposed.jpg', 100, 'image/jpeg')],
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
            'proposed_situation_images' => [UploadedFile::fake()->create('proposed.jpg', 100, 'image/jpeg')],
        ];

        $response = $this->actingAs($this->user)->post(route('kaizens.store'), $payload);

        $response->assertSessionHasErrors('current_situation_images.0');
        $this->assertEquals(0, Kaizen::count());
    }

    public function test_misleading_extension_rejection(): void
    {
        $file = UploadedFile::fake()->create('photo.jpg', 100, 'text/plain');

        $payload = [
            'category_id' => $this->category->id,
            'title' => 'Misleading extension test',
            'current_situation' => 'Current situation details',
            'proposed_situation' => 'Proposed situation details',
            'expected_benefit' => 'Expected benefit details',
            'current_situation_images' => [$file],
            'proposed_situation_images' => [UploadedFile::fake()->create('proposed.jpg', 100, 'image/jpeg')],
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
            'proposed_situation_images' => [UploadedFile::fake()->create('proposed.jpg', 100, 'image/jpeg')],
        ];

        $response = $this->actingAs($this->user)->post(route('kaizens.store'), $payload);

        $response->assertSessionHasErrors('current_situation');
        $this->assertEquals(0, Kaizen::count());
    }

    public function test_storage_failure_rollback_integration(): void
    {
        $this->withoutExceptionHandling();

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
            'proposed_situation_images' => [UploadedFile::fake()->create('proposed.jpg', 100, 'image/jpeg')],
        ];

        try {
            $this->actingAs($this->user)->post(route('kaizens.store'), $payload);
            $this->fail('Expected exception was not thrown.');
        } catch (\Exception $e) {
            $this->assertEquals('Storage simulated failure', $e->getMessage());
        }

        $this->assertEquals(0, Kaizen::count());
        $this->assertEquals(0, KaizenAttachment::count());
    }

    public function test_create_outer_transaction_rollback_cleans_new_physical(): void
    {
        $this->withoutExceptionHandling();
        Storage::fake('local');

        $currentImage = UploadedFile::fake()->create('current.jpg', 10, 'image/jpeg');
        $proposedImage = UploadedFile::fake()->create('proposed.jpg', 10, 'image/jpeg');

        $payload = [
            'category_id' => $this->category->id,
            'title' => 'Outer rollback test',
            'current_situation' => 'Current situation details',
            'proposed_situation' => 'Proposed situation details',
            'expected_benefit' => 'Expected benefit details',
            'current_situation_images' => [$currentImage],
            'proposed_situation_images' => [$proposedImage],
        ];

        // Mock KaizenAttachmentService to throw exception on the SECOND call (PROPOSED_SITUATION)
        $mockService = \Mockery::mock(KaizenAttachmentService::class)->makePartial();

        // Allow the first call (CURRENT) to pass through to the real implementation
        $mockService->shouldReceive('storeMany')
            ->withArgs(function ($kaizen, $creator, $context, $files) {
                return $context === KaizenAttachmentContext::CURRENT_SITUATION;
            })
            ->passthru();

        // Throw exception on the second call (PROPOSED)
        $mockService->shouldReceive('storeMany')
            ->withArgs(function ($kaizen, $creator, $context, $files) {
                return $context === KaizenAttachmentContext::PROPOSED_SITUATION;
            })
            ->andThrow(new \Exception('Simulated proposed upload failure.'));

        $this->app->instance(KaizenAttachmentService::class, $mockService);

        try {
            $this->actingAs($this->user)->post(route('kaizens.store'), $payload);
            $this->fail('Expected exception was not thrown.');
        } catch (\Exception $e) {
            $this->assertEquals('Simulated proposed upload failure.', $e->getMessage());
        }

        // DB State
        $this->assertEquals(0, Kaizen::count());
        $this->assertEquals(0, KaizenAttachment::count());

        // Physical state: The outer transaction catch block should have cleaned up the successful first upload
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }
}
