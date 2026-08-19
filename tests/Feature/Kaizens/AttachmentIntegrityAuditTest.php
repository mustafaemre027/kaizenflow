<?php

namespace Tests\Feature\Kaizens;

use App\Console\Commands\AuditKaizenAttachments;
use App\Models\Kaizen;
use App\Models\KaizenAttachment;
use App\Services\Kaizens\KaizenAttachmentIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentIntegrityAuditTest extends TestCase
{
    use RefreshDatabase;

    private KaizenAttachmentIntegrityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(KaizenAttachmentIntegrityService::class);
        Storage::fake('local');
    }

    // ─── Prefix safety ────────────────────────────────────────────────────────

    public function test_empty_prefix_is_unsafe(): void
    {
        config(['kaizen.attachments.managed_prefix' => '']);
        $this->assertFalse($this->service->isManagedPrefixSafe());
    }

    public function test_root_slash_prefix_is_unsafe(): void
    {
        config(['kaizen.attachments.managed_prefix' => '/']);
        $this->assertFalse($this->service->isManagedPrefixSafe());
    }

    public function test_single_char_prefix_is_unsafe(): void
    {
        config(['kaizen.attachments.managed_prefix' => 'k']);
        $this->assertFalse($this->service->isManagedPrefixSafe());
    }

    public function test_path_traversal_prefix_is_unsafe(): void
    {
        config(['kaizen.attachments.managed_prefix' => '../etc']);
        $this->assertFalse($this->service->isManagedPrefixSafe());
    }

    public function test_valid_prefix_is_safe(): void
    {
        config(['kaizen.attachments.managed_prefix' => 'kaizens']);
        $this->assertTrue($this->service->isManagedPrefixSafe());
    }

    // ─── Path boundary ────────────────────────────────────────────────────────

    public function test_path_within_managed_boundary(): void
    {
        $this->assertTrue(
            $this->service->isPathWithinManagedBoundary('kaizens/1/evidence/current/file.jpg', 'kaizens')
        );
    }

    public function test_path_outside_managed_boundary(): void
    {
        $this->assertFalse(
            $this->service->isPathWithinManagedBoundary('other-module/important.txt', 'kaizens')
        );
    }

    public function test_path_not_prefixed_with_managed_boundary_even_if_similar(): void
    {
        $this->assertFalse(
            $this->service->isPathWithinManagedBoundary('kaizensfoo/file.jpg', 'kaizens')
        );
    }

    // ─── DB audit — healthy ───────────────────────────────────────────────────

    public function test_healthy_attachments_return_ok(): void
    {
        $kaizen = Kaizen::factory()->create();
        $path1 = 'kaizens/1/evidence/current/a.jpg';
        $path2 = 'kaizens/1/evidence/current/b.jpg';
        Storage::disk('local')->put($path1, 'content-a');
        Storage::disk('local')->put($path2, 'content-b');

        KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizen->id,
            'storage_disk' => 'local',
            'storage_path' => $path1,
            'mime_type' => 'image/jpeg',
            'size_bytes' => strlen('content-a'),
            'sha256' => hash('sha256', 'content-a'),
        ]);
        KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizen->id,
            'storage_disk' => 'local',
            'storage_path' => $path2,
            'mime_type' => 'image/jpeg',
            'size_bytes' => strlen('content-b'),
            'sha256' => hash('sha256', 'content-b'),
        ]);

        $result = $this->service->audit();

        $this->assertEquals(2, $result['summary'][KaizenAttachmentIntegrityService::STATUS_OK]);
        $this->assertEquals(0, $result['summary'][KaizenAttachmentIntegrityService::STATUS_MISSING_FILE]);
        $this->assertEquals(0, $result['summary'][KaizenAttachmentIntegrityService::STATUS_ORPHAN_FILE]);
    }

    // ─── DB audit — missing file ──────────────────────────────────────────────

    public function test_missing_physical_file_is_reported(): void
    {
        $kaizen = Kaizen::factory()->create();
        $attachment = KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizen->id,
            'storage_disk' => 'local',
            'storage_path' => 'kaizens/1/evidence/current/missing.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 100,
        ]);

        $result = $this->service->audit();

        $this->assertEquals(1, $result['summary'][KaizenAttachmentIntegrityService::STATUS_MISSING_FILE]);
        $this->assertEquals(0, $result['summary'][KaizenAttachmentIntegrityService::STATUS_OK]);

        // DB row must still exist
        $this->assertDatabaseHas('kaizen_attachments', ['id' => $attachment->id]);
    }

    // ─── DB audit — invalid MIME ──────────────────────────────────────────────

    public function test_invalid_mime_is_reported_no_deletion(): void
    {
        $kaizen = Kaizen::factory()->create();
        $path = 'kaizens/1/evidence/current/svg.svg';
        Storage::disk('local')->put($path, '<svg/>');

        $attachment = KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizen->id,
            'storage_disk' => 'local',
            'storage_path' => $path,
            'mime_type' => 'image/svg+xml',
            'size_bytes' => strlen('<svg/>'),
        ]);

        $result = $this->service->audit();

        $this->assertEquals(1, $result['summary'][KaizenAttachmentIntegrityService::STATUS_INVALID_MIME]);

        // File and DB row must both still exist
        Storage::disk('local')->assertExists($path);
        $this->assertDatabaseHas('kaizen_attachments', ['id' => $attachment->id]);
    }

    // ─── DB audit — invalid disk ──────────────────────────────────────────────

    public function test_invalid_disk_metadata_is_reported(): void
    {
        $kaizen = Kaizen::factory()->create();
        KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizen->id,
            'storage_disk' => 'unknown_disk',
            'storage_path' => 'kaizens/1/evidence/current/file.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 100,
        ]);

        $result = $this->service->audit();

        $this->assertEquals(1, $result['summary'][KaizenAttachmentIntegrityService::STATUS_INVALID_DISK]);
    }

    // ─── DB audit — unsafe path ───────────────────────────────────────────────

    public function test_unsafe_stored_path_is_reported(): void
    {
        $kaizen = Kaizen::factory()->create();
        KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizen->id,
            'storage_disk' => 'local',
            'storage_path' => 'other-module/secrets/important.txt',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 100,
        ]);

        $result = $this->service->audit();

        $this->assertEquals(1, $result['summary'][KaizenAttachmentIntegrityService::STATUS_UNSAFE_PATH]);
    }

    // ─── DB audit — hash verification ────────────────────────────────────────

    public function test_hash_mismatch_is_reported_with_flag(): void
    {
        $kaizen = Kaizen::factory()->create();
        $path = 'kaizens/1/evidence/current/tampered.jpg';
        Storage::disk('local')->put($path, 'actual-content');
        $physicalSize = strlen('actual-content');

        KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizen->id,
            'storage_disk' => 'local',
            'storage_path' => $path,
            'mime_type' => 'image/jpeg',
            'size_bytes' => $physicalSize,
            'sha256' => hash('sha256', 'original-different-content'),
        ]);

        $result = $this->service->audit(verifyHashes: true);

        $this->assertEquals(1, $result['summary'][KaizenAttachmentIntegrityService::STATUS_HASH_MISMATCH]);

        // File and DB must both be preserved
        Storage::disk('local')->assertExists($path);
        $this->assertDatabaseCount('kaizen_attachments', 1);
    }

    public function test_matching_hash_returns_ok(): void
    {
        $kaizen = Kaizen::factory()->create();
        $content = 'genuine-image-content';
        $path = 'kaizens/1/evidence/current/genuine.jpg';
        Storage::disk('local')->put($path, $content);

        KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizen->id,
            'storage_disk' => 'local',
            'storage_path' => $path,
            'mime_type' => 'image/jpeg',
            'size_bytes' => strlen($content),
            'sha256' => hash('sha256', $content),
        ]);

        $result = $this->service->audit(verifyHashes: true);

        $this->assertEquals(1, $result['summary'][KaizenAttachmentIntegrityService::STATUS_OK]);
        $this->assertEquals(0, $result['summary'][KaizenAttachmentIntegrityService::STATUS_HASH_MISMATCH]);
    }

    // ─── Orphan detection ─────────────────────────────────────────────────────

    public function test_orphan_file_is_detected(): void
    {
        // No DB record — only physical file under managed prefix
        Storage::disk('local')->put('kaizens/1/evidence/current/orphan.jpg', 'data');

        // Freeze time so the file is old enough
        $this->travel(-20)->minutes();
        Storage::disk('local')->put('kaizens/1/evidence/current/orphan.jpg', 'data');
        $this->travelBack();

        $orphanResults = $this->service->auditOrphanFiles();

        $this->assertGreaterThanOrEqual(1, $orphanResults->count());
    }

    public function test_file_outside_managed_prefix_is_not_detected_as_orphan(): void
    {
        Storage::disk('local')->put('other-module/important.txt', 'secret data');

        $orphanResults = $this->service->auditOrphanFiles();

        // other-module/ should not appear since we only scan kaizens/
        $paths = $orphanResults->pluck('path');
        $this->assertFalse($paths->contains('other-module/important.txt'));
    }

    // ─── Orphan cleanup ───────────────────────────────────────────────────────

    public function test_default_audit_does_not_delete_orphans(): void
    {
        Storage::disk('local')->put('kaizens/1/evidence/current/orphan.jpg', 'data');

        $this->artisan('kaizen:attachments:audit');

        Storage::disk('local')->assertExists('kaizens/1/evidence/current/orphan.jpg');
    }

    public function test_orphan_beyond_grace_period_is_deleted_with_flag(): void
    {
        config(['kaizen.attachments.orphan_grace_minutes' => 0]);

        $path = 'kaizens/1/evidence/current/old-orphan.jpg';
        Storage::disk('local')->put($path, 'stale-data');

        // No DB record — this is a genuine orphan with 0-minute grace period
        $orphanResults = $this->service->auditOrphanFiles();
        $cleanup = $this->service->deleteOrphanFiles($orphanResults);

        $this->assertGreaterThanOrEqual(1, $cleanup['deleted']);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_recent_orphan_within_grace_period_is_preserved(): void
    {
        config(['kaizen.attachments.orphan_grace_minutes' => 60]);

        $path = 'kaizens/1/evidence/current/new-orphan.jpg';
        Storage::disk('local')->put($path, 'new-data');

        $orphanResults = $this->service->auditOrphanFiles();
        $cleanup = $this->service->deleteOrphanFiles($orphanResults);

        // File should be too new to delete
        $this->assertEquals(0, $cleanup['deleted']);
        Storage::disk('local')->assertExists($path);
    }

    public function test_unrelated_db_files_are_preserved_during_orphan_cleanup(): void
    {
        config(['kaizen.attachments.orphan_grace_minutes' => 0]);

        $kaizen = Kaizen::factory()->create();
        $validPath = 'kaizens/1/evidence/current/valid.jpg';
        $orphanPath = 'kaizens/1/evidence/current/orphan.jpg';

        Storage::disk('local')->put($validPath, 'real-content');
        Storage::disk('local')->put($orphanPath, 'orphan-content');

        KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizen->id,
            'storage_disk' => 'local',
            'storage_path' => $validPath,
            'mime_type' => 'image/jpeg',
            'size_bytes' => strlen('real-content'),
        ]);

        $orphanResults = $this->service->auditOrphanFiles();
        $this->service->deleteOrphanFiles($orphanResults);

        // Valid DB-backed file must survive
        Storage::disk('local')->assertExists($validPath);
        Storage::disk('local')->assertMissing($orphanPath);
    }

    public function test_unsafe_path_orphan_is_never_deleted(): void
    {
        config(['kaizen.attachments.managed_prefix' => 'kaizens']);
        config(['kaizen.attachments.orphan_grace_minutes' => 0]);

        // This orphan is outside managed prefix — should not be touched
        $results = collect([
            [
                'path' => 'other-module/critical.txt',
                'status' => KaizenAttachmentIntegrityService::STATUS_ORPHAN_FILE,
                'detail' => 'No DB record.',
                'age_minutes' => 9999,
            ],
        ]);

        Storage::disk('local')->put('other-module/critical.txt', 'important');
        $cleanup = $this->service->deleteOrphanFiles($results);

        $this->assertEquals(0, $cleanup['deleted']);
        $this->assertEquals(1, $cleanup['skipped']);
        Storage::disk('local')->assertExists('other-module/critical.txt');
    }

    // ─── DB mutation safety ───────────────────────────────────────────────────

    public function test_default_audit_does_not_mutate_database(): void
    {
        $kaizen = Kaizen::factory()->create();
        KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizen->id,
            'storage_disk' => 'local',
            'storage_path' => 'kaizens/1/evidence/current/missing.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 100,
        ]);

        $countBefore = KaizenAttachment::count();

        $this->service->audit();

        $this->assertEquals($countBefore, KaizenAttachment::count());
    }

    // ─── Failed post-commit delete recovery ──────────────────────────────────

    public function test_failed_post_commit_delete_leaves_orphan_recoverable(): void
    {
        config(['kaizen.attachments.orphan_grace_minutes' => 0]);

        // Simulate: DB row was deleted (commit succeeded), but physical delete failed
        $path = 'kaizens/99/evidence/current/leftover.jpg';
        Storage::disk('local')->put($path, 'leftover-data');

        // No DB record (simulating post-commit DB delete succeeded, physical failed)
        $orphanResults = $this->service->auditOrphanFiles();
        $orphans = $orphanResults->where('status', KaizenAttachmentIntegrityService::STATUS_ORPHAN_FILE);

        $this->assertGreaterThanOrEqual(1, $orphans->count());

        // Cleanup should recover it
        $cleanup = $this->service->deleteOrphanFiles($orphanResults);
        $this->assertGreaterThanOrEqual(1, $cleanup['deleted']);
        Storage::disk('local')->assertMissing($path);
    }

    // ─── Artisan command ──────────────────────────────────────────────────────

    public function test_command_exits_zero_when_healthy(): void
    {
        $kaizen = Kaizen::factory()->create();
        $path = 'kaizens/1/evidence/current/healthy.jpg';
        $content = 'healthy-content';
        Storage::disk('local')->put($path, $content);

        KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizen->id,
            'storage_disk' => 'local',
            'storage_path' => $path,
            'mime_type' => 'image/jpeg',
            'size_bytes' => strlen($content),
            'sha256' => hash('sha256', $content),
        ]);

        $this->artisan('kaizen:attachments:audit')->assertExitCode(AuditKaizenAttachments::SUCCESS);
    }

    public function test_command_exits_nonzero_when_anomalies_found(): void
    {
        Kaizen::factory()->create();
        KaizenAttachment::factory()->create([
            'storage_disk' => 'local',
            'storage_path' => 'kaizens/1/evidence/current/missing.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 100,
        ]);

        $this->artisan('kaizen:attachments:audit')->assertExitCode(AuditKaizenAttachments::FAILURE);
    }

    public function test_command_aborts_with_unsafe_prefix(): void
    {
        config(['kaizen.attachments.managed_prefix' => '']);

        $this->artisan('kaizen:attachments:audit')->assertExitCode(AuditKaizenAttachments::FAILURE);
    }

    public function test_command_no_delete_without_flag(): void
    {
        config(['kaizen.attachments.orphan_grace_minutes' => 0]);

        Storage::disk('local')->put('kaizens/1/evidence/current/orphan.jpg', 'data');

        $this->artisan('kaizen:attachments:audit');

        Storage::disk('local')->assertExists('kaizens/1/evidence/current/orphan.jpg');
    }

    public function test_command_deletes_orphan_with_flag(): void
    {
        config(['kaizen.attachments.orphan_grace_minutes' => 0]);

        Storage::disk('local')->put('kaizens/1/evidence/current/old.jpg', 'old-data');

        $this->artisan('kaizen:attachments:audit --delete-orphans');

        Storage::disk('local')->assertMissing('kaizens/1/evidence/current/old.jpg');
    }

    public function test_command_with_verify_hashes_detects_mismatch(): void
    {
        $kaizen = Kaizen::factory()->create();
        $path = 'kaizens/1/evidence/current/tampered.jpg';
        Storage::disk('local')->put($path, 'actual-bytes');

        KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizen->id,
            'storage_disk' => 'local',
            'storage_path' => $path,
            'mime_type' => 'image/jpeg',
            'size_bytes' => strlen('actual-bytes'),
            'sha256' => hash('sha256', 'different-original-content'),
        ]);

        $this->artisan('kaizen:attachments:audit --verify-hashes')
            ->assertExitCode(AuditKaizenAttachments::FAILURE);
    }
}
