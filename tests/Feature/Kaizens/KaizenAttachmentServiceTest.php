<?php

namespace Tests\Feature\Kaizens;

use App\Enums\KaizenAttachmentContext;
use App\Models\Kaizen;
use App\Models\KaizenAttachment;
use App\Models\User;
use App\Services\Kaizens\KaizenAttachmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KaizenAttachmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private KaizenAttachmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(KaizenAttachmentService::class);
        Storage::fake('local');
    }

    public function test_store_one_attachment_successfully(): void
    {
        $kaizen = Kaizen::factory()->create();
        $uploader = User::factory()->create();
        $file = UploadedFile::fake()->create('evidence1.jpg', 100, 'image/jpeg');

        $attachments = $this->service->storeMany($kaizen, $uploader, KaizenAttachmentContext::CURRENT_SITUATION, [$file]);

        $this->assertCount(1, $attachments);
        $attachment = $attachments->first();

        // DB Assertions
        $this->assertDatabaseHas('kaizen_attachments', [
            'id' => $attachment->id,
            'kaizen_id' => $kaizen->id,
            'uploaded_by_user_id' => $uploader->id,
            'context' => KaizenAttachmentContext::CURRENT_SITUATION->value,
            'original_name' => 'evidence1.jpg',
            'storage_disk' => 'local',
            'mime_type' => 'image/jpeg',
            'sort_order' => 1,
        ]);

        $this->assertEquals(64, strlen($attachment->sha256));

        // Storage Assertions
        Storage::disk('local')->assertExists($attachment->storage_path);

        // original name is not the path
        $this->assertNotEquals('evidence1.jpg', $attachment->storage_path);
        $this->assertStringContainsString("kaizens/{$kaizen->id}/evidence/current_situation", $attachment->storage_path);
    }

    public function test_store_multiple_attachments_with_sort_order(): void
    {
        $kaizen = Kaizen::factory()->create();
        $uploader = User::factory()->create();

        $file1 = UploadedFile::fake()->create('img1.jpg', 100, 'image/jpeg');
        $file2 = UploadedFile::fake()->create('img2.jpg', 100, 'image/jpeg');

        $attachments = $this->service->storeMany($kaizen, $uploader, KaizenAttachmentContext::PROPOSED_SITUATION, [$file1, $file2]);

        $this->assertCount(2, $attachments);

        $this->assertEquals(1, $attachments[0]->sort_order);
        $this->assertEquals(2, $attachments[1]->sort_order);

        Storage::disk('local')->assertExists($attachments[0]->storage_path);
        Storage::disk('local')->assertExists($attachments[1]->storage_path);
    }

    public function test_context_isolation(): void
    {
        $kaizen = Kaizen::factory()->create();
        $uploader = User::factory()->create();

        $file1 = UploadedFile::fake()->create('img1.jpg', 100, 'image/jpeg');
        $file2 = UploadedFile::fake()->create('img2.jpg', 100, 'image/jpeg');

        $this->service->storeMany($kaizen, $uploader, KaizenAttachmentContext::CURRENT_SITUATION, [$file1]);
        $this->service->storeMany($kaizen, $uploader, KaizenAttachmentContext::PROPOSED_SITUATION, [$file2]);

        $currents = $kaizen->attachments()->where('context', KaizenAttachmentContext::CURRENT_SITUATION)->get();
        $proposed = $kaizen->attachments()->where('context', KaizenAttachmentContext::PROPOSED_SITUATION)->get();

        $this->assertCount(1, $currents);
        $this->assertCount(1, $proposed);

        $this->assertEquals(1, $currents->first()->sort_order);
        $this->assertEquals(1, $proposed->first()->sort_order); // Sort should be isolated by context

        $this->assertStringContainsString('current_situation', $currents->first()->storage_path);
        $this->assertStringContainsString('proposed_situation', $proposed->first()->storage_path);
    }

    public function test_path_traversal_is_prevented(): void
    {
        $kaizen = Kaizen::factory()->create();
        $uploader = User::factory()->create();

        $file = UploadedFile::fake()->create('../../evil.jpg', 100, 'image/jpeg');

        $attachments = $this->service->storeMany($kaizen, $uploader, KaizenAttachmentContext::CURRENT_SITUATION, [$file]);

        $attachment = $attachments->first();

        $this->assertEquals('evil.jpg', $attachment->original_name);
        $this->assertStringNotContainsString('..', $attachment->storage_path);
        Storage::disk('local')->assertExists($attachment->storage_path);
    }

    public function test_compensating_cleanup_on_db_failure(): void
    {
        $kaizen = Kaizen::factory()->create();
        $uploader = User::factory()->create();

        $file = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg');

        // We will mock the database insert to throw an exception to test the rollback and cleanup
        KaizenAttachment::saving(function () {
            throw new \Exception('DB Failure Simulated');
        });

        try {
            $this->service->storeMany($kaizen, $uploader, KaizenAttachmentContext::CURRENT_SITUATION, [$file]);
            $this->fail('Exception was not thrown.');
        } catch (\Exception $e) {
            $this->assertEquals('DB Failure Simulated', $e->getMessage());
        }

        // Verify DB rollback
        $this->assertDatabaseCount('kaizen_attachments', 0);

        // Verify storage cleanup
        $filesInStorage = Storage::disk('local')->allFiles("kaizens/{$kaizen->id}");
        $this->assertEmpty($filesInStorage, 'Physical files should be cleaned up on DB failure');
    }

    public function test_delete_physical_files_skips_outside_paths_and_unallowed_disks(): void
    {
        Storage::fake('local');
        Storage::fake('s3');
        config(['kaizen.attachments.managed_prefix' => 'kaizens']);
        config(['kaizen.attachments.allowed_disks' => ['local']]);

        // 1. Outside path
        $outsidePath = 'other_module/secret.jpg';
        Storage::disk('local')->put($outsidePath, 'secret data');
        $outsideAttachment = KaizenAttachment::factory()->make([
            'id' => 998,
            'storage_path' => $outsidePath,
            'storage_disk' => 'local',
        ]);

        // 2. Unallowed disk
        $unallowedPath = 'kaizens/1/evidence/test.jpg';
        Storage::disk('s3')->put($unallowedPath, 's3 data');
        $unallowedAttachment = KaizenAttachment::factory()->make([
            'id' => 999,
            'storage_path' => $unallowedPath,
            'storage_disk' => 's3',
        ]);

        $this->service->deletePhysicalFiles(collect([$outsideAttachment, $unallowedAttachment]));

        // Both files should still exist
        Storage::disk('local')->assertExists($outsidePath);
        Storage::disk('s3')->assertExists($unallowedPath);
    }

    public function test_delete_physical_files_deletes_valid_attachments(): void
    {
        Storage::fake('local');
        config(['kaizen.attachments.managed_prefix' => 'kaizens']);
        config(['kaizen.attachments.allowed_disks' => ['local']]);

        $validPath = 'kaizens/1/evidence/current/valid.jpg';
        Storage::disk('local')->put($validPath, 'valid data');

        $attachment = KaizenAttachment::factory()->make([
            'id' => 1000,
            'storage_path' => $validPath,
            'storage_disk' => 'local',
        ]);

        $this->service->deletePhysicalFiles(collect([$attachment]));

        Storage::disk('local')->assertMissing($validPath);
    }

    public function test_delete_physical_files_skips_traversal_paths(): void
    {
        Storage::fake('local');
        config(['kaizen.attachments.managed_prefix' => 'kaizens']);
        config(['kaizen.attachments.allowed_disks' => ['local']]);

        // Synthetic corrupted attachment with path traversal
        $traversalPath = 'kaizens/../other-module/critical.jpg';

        // Setup the physical file
        Storage::disk('local')->put('other-module/critical.jpg', 'critical data');

        $attachment = KaizenAttachment::factory()->make([
            'id' => 1001,
            'storage_path' => $traversalPath,
            'storage_disk' => 'local',
        ]);

        $this->service->deletePhysicalFiles(collect([$attachment]));

        // The critical file must STILL EXIST
        Storage::disk('local')->assertExists('other-module/critical.jpg');
    }
}
