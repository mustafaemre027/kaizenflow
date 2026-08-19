<?php

namespace Tests\Feature\Http\Controllers;

use App\Actions\Kaizens\UpdateKaizenDraft;
use App\Enums\KaizenAttachmentContext;
use App\Enums\KaizenStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\KaizenAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KaizenEditEvidenceTest extends TestCase
{
    use RefreshDatabase;

    private User $activeUser;

    private Department $department;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create(['is_active' => true]);
        $this->activeUser = User::factory()->create([
            'is_active' => true,
            'department_id' => $this->department->id,
            'role' => UserRole::EMPLOYEE,
        ]);
        $this->category = Category::factory()->create(['is_active' => true]);
    }

    public function test_edit_view_loads_existing_attachments()
    {
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $this->activeUser->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        KaizenAttachment::factory()->count(2)->create([
            'kaizen_id' => $kaizen->id,
            'context' => KaizenAttachmentContext::CURRENT_SITUATION,
        ]);

        KaizenAttachment::factory()->count(3)->create([
            'kaizen_id' => $kaizen->id,
            'context' => KaizenAttachmentContext::PROPOSED_SITUATION,
        ]);

        $response = $this->actingAs($this->activeUser)
            ->get(route('kaizens.edit', $kaizen));

        $response->assertOk();
        $response->assertViewHas('currentSituationAttachments');
        $response->assertViewHas('proposedSituationAttachments');

        $this->assertCount(2, $response->viewData('currentSituationAttachments'));
        $this->assertCount(3, $response->viewData('proposedSituationAttachments'));
    }

    public function test_add_current_attachments()
    {
        Storage::fake('local');

        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $this->activeUser->id,
            'status' => KaizenStatus::DRAFT,
            'category_id' => $this->category->id,
            'title' => 'Eski Başlık',
        ]);

        KaizenAttachment::factory()->count(2)->create([
            'kaizen_id' => $kaizen->id,
            'context' => KaizenAttachmentContext::CURRENT_SITUATION,
        ]);

        $file1 = UploadedFile::fake()->create('', 10, 'image/jpeg');
        $file2 = UploadedFile::fake()->create('', 10, 'image/jpeg');

        $response = $this->actingAs($this->activeUser)
            ->patch(route('kaizens.update', $kaizen), [
                'title' => 'Yeni Başlık',
                'current_situation_images' => [$file1, $file2],
            ]);

        $response->assertRedirect(route('kaizens.show', $kaizen));

        $this->assertDatabaseHas('kaizens', [
            'id' => $kaizen->id,
            'title' => 'Yeni Başlık',
        ]);

        $this->assertEquals(4, $kaizen->attachments()->where('context', KaizenAttachmentContext::CURRENT_SITUATION)->count());
    }

    public function test_add_proposed_attachments()
    {
        Storage::fake('local');

        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $this->activeUser->id,
            'status' => KaizenStatus::DRAFT,
            'category_id' => $this->category->id,
            'title' => 'Eski Başlık',
        ]);

        KaizenAttachment::factory()->count(3)->create([
            'kaizen_id' => $kaizen->id,
            'context' => KaizenAttachmentContext::PROPOSED_SITUATION,
        ]);

        $file1 = UploadedFile::fake()->create('', 10, 'image/jpeg');
        $file2 = UploadedFile::fake()->create('', 10, 'image/jpeg');

        $response = $this->actingAs($this->activeUser)
            ->patch(route('kaizens.update', $kaizen), [
                'title' => 'Yeni Başlık',
                'proposed_situation_images' => [$file1, $file2],
            ]);

        $response->assertRedirect(route('kaizens.show', $kaizen));
        $this->assertEquals(5, $kaizen->attachments()->where('context', KaizenAttachmentContext::PROPOSED_SITUATION)->count());
    }

    public function test_remove_attachment()
    {
        Storage::fake('local');

        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $this->activeUser->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        $file = UploadedFile::fake()->create('', 10, 'image/jpeg');
        $path = $file->store('kaizens/1/evidence/current', 'local');

        $attachment = KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizen->id,
            'context' => KaizenAttachmentContext::CURRENT_SITUATION,
            'storage_path' => $path,
            'storage_disk' => 'local',
        ]);

        $this->assertTrue(Storage::disk('local')->exists($path));

        $response = $this->actingAs($this->activeUser)
            ->patch(route('kaizens.update', $kaizen), [
                'title' => 'Yeni Başlık',
                'remove_attachment_ids' => [$attachment->id],
            ]);

        $response->assertRedirect(route('kaizens.show', $kaizen));

        $this->assertDatabaseMissing('kaizen_attachments', [
            'id' => $attachment->id,
        ]);

        $this->assertFalse(Storage::disk('local')->exists($path));
    }

    public function test_remove_unrelated_attachment_fails()
    {
        Storage::fake('local');

        $kaizenA = Kaizen::factory()->create([
            'creator_user_id' => $this->activeUser->id,
            'status' => KaizenStatus::DRAFT,
            'title' => 'Kaizen A',
        ]);

        $kaizenB = Kaizen::factory()->create([
            'creator_user_id' => $this->activeUser->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        $attachmentB = KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizenB->id,
            'context' => KaizenAttachmentContext::CURRENT_SITUATION,
        ]);

        $response = $this->actingAs($this->activeUser)
            ->patch(route('kaizens.update', $kaizenA), [
                'title' => 'Yeni Başlık A',
                'remove_attachment_ids' => [$attachmentB->id],
            ]);

        $response->assertInvalid(['payload' => 'Geçersiz fotoğraf kaldırma isteği.']);

        $this->assertDatabaseHas('kaizens', [
            'id' => $kaizenA->id,
            'title' => 'Kaizen A',
        ]);

        $this->assertDatabaseHas('kaizen_attachments', [
            'id' => $attachmentB->id,
        ]);
    }

    public function test_remove_and_add_in_same_request()
    {
        Storage::fake('local');

        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $this->activeUser->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        $attachments = KaizenAttachment::factory()->count(8)->create([
            'kaizen_id' => $kaizen->id,
            'context' => KaizenAttachmentContext::CURRENT_SITUATION,
        ]);

        $removeIds = $attachments->take(2)->pluck('id')->toArray();

        $file1 = UploadedFile::fake()->create('', 10, 'image/jpeg');
        $file2 = UploadedFile::fake()->create('', 10, 'image/jpeg');

        $response = $this->actingAs($this->activeUser)
            ->patch(route('kaizens.update', $kaizen), [
                'title' => 'Updated Title',
                'remove_attachment_ids' => $removeIds,
                'current_situation_images' => [$file1, $file2],
            ]);

        $response->assertRedirect(route('kaizens.show', $kaizen));

        $this->assertEquals(8, $kaizen->attachments()->where('context', KaizenAttachmentContext::CURRENT_SITUATION)->count());
    }

    public function test_effective_overflow_fails()
    {
        Storage::fake('local');

        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $this->activeUser->id,
            'status' => KaizenStatus::DRAFT,
            'title' => 'Old Title',
        ]);

        KaizenAttachment::factory()->count(7)->create([
            'kaizen_id' => $kaizen->id,
            'context' => KaizenAttachmentContext::CURRENT_SITUATION,
        ]);

        $file1 = UploadedFile::fake()->create('', 10, 'image/jpeg');
        $file2 = UploadedFile::fake()->create('', 10, 'image/jpeg');

        $response = $this->actingAs($this->activeUser)
            ->patch(route('kaizens.update', $kaizen), [
                'title' => 'New Title',
                'current_situation_images' => [$file1, $file2], // 7 + 2 = 9 > 8
            ]);

        $response->assertInvalid(['current_situation_images' => 'Mevcut durum için en fazla 8 fotoğraf bulunabilir.']);

        $this->assertDatabaseHas('kaizens', [
            'id' => $kaizen->id,
            'title' => 'Old Title', // Rolled back
        ]);

        $this->assertEquals(7, $kaizen->attachments()->where('context', KaizenAttachmentContext::CURRENT_SITUATION)->count());
    }

    public function test_non_editable_status_cannot_be_updated()
    {
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $this->activeUser->id,
            'status' => KaizenStatus::SUBMITTED,
        ]);

        $file1 = UploadedFile::fake()->create('', 10, 'image/jpeg');

        $response = $this->actingAs($this->activeUser)
            ->patch(route('kaizens.update', $kaizen), [
                'title' => 'New Title',
                'current_situation_images' => [$file1],
            ]);

        $response->assertForbidden();
        $this->assertEquals(0, $kaizen->attachments()->count());
    }

    public function test_other_user_cannot_edit()
    {
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $this->activeUser->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        $otherUser = User::factory()->create([
            'is_active' => true,
            'department_id' => $this->department->id,
            'role' => UserRole::EMPLOYEE,
        ]);

        $file1 = UploadedFile::fake()->create('', 10, 'image/jpeg');

        $response = $this->actingAs($otherUser)
            ->patch(route('kaizens.update', $kaizen), [
                'title' => 'New Title',
                'current_situation_images' => [$file1],
            ]);

        $response->assertForbidden();
        $this->assertEquals(0, $kaizen->attachments()->count());
    }

    public function test_zero_photo_update_works()
    {
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $this->activeUser->id,
            'status' => KaizenStatus::DRAFT,
            'title' => 'Old Title',
        ]);

        $response = $this->actingAs($this->activeUser)
            ->patch(route('kaizens.update', $kaizen), [
                'title' => 'New Title',
            ]);

        $response->assertRedirect(route('kaizens.show', $kaizen));

        $this->assertDatabaseHas('kaizens', [
            'id' => $kaizen->id,
            'title' => 'New Title',
        ]);
    }

    public function test_keep_all_existing_attachments_without_changes()
    {
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $this->activeUser->id,
            'status' => KaizenStatus::DRAFT,
            'title' => 'Old Title',
        ]);

        KaizenAttachment::factory()->count(2)->create([
            'kaizen_id' => $kaizen->id,
            'context' => KaizenAttachmentContext::CURRENT_SITUATION,
        ]);

        $response = $this->actingAs($this->activeUser)
            ->patch(route('kaizens.update', $kaizen), [
                'title' => 'New Title',
            ]);

        $response->assertRedirect(route('kaizens.show', $kaizen));

        $this->assertEquals(2, $kaizen->attachments()->count());
    }

    public function test_accept_image_size_between_5_and_8_mb()
    {
        Storage::fake('local');

        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $this->activeUser->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        // 6 MB image (6144 KB)
        $file = UploadedFile::fake()->create('large.jpg', 6144, 'image/jpeg');

        $response = $this->actingAs($this->activeUser)
            ->patch(route('kaizens.update', $kaizen), [
                'title' => 'New Title',
                'current_situation_images' => [$file],
            ]);

        $response->assertRedirect(route('kaizens.show', $kaizen));
        $this->assertEquals(1, $kaizen->attachments()->count());
    }

    public function test_reject_image_size_above_config_max()
    {
        Storage::fake('local');

        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $this->activeUser->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        // Max is 8192 KB, so let's send 8200 KB
        $file = UploadedFile::fake()->create('oversize.jpg', 8200, 'image/jpeg');

        $response = $this->actingAs($this->activeUser)
            ->patch(route('kaizens.update', $kaizen), [
                'title' => 'New Title',
                'current_situation_images' => [$file],
            ]);

        $response->assertInvalid(['current_situation_images.0']);

        $errors = session('errors')->getBag('default')->get('current_situation_images.0');
        $this->assertStringContainsString('izin verilen dosya boyutunu aşıyor', $errors[0]);

        $this->assertEquals(0, $kaizen->attachments()->count());
    }

    public function test_failed_validation_preserves_existing_attachments_and_metadata()
    {
        Storage::fake('local');

        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $this->activeUser->id,
            'status' => KaizenStatus::DRAFT,
            'title' => 'Old Title',
        ]);

        $file = UploadedFile::fake()->create('test.jpg', 10, 'image/jpeg');
        $path = $file->store('kaizens/1/evidence/current', 'local');

        $attachment = KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizen->id,
            'context' => KaizenAttachmentContext::CURRENT_SITUATION,
            'storage_path' => $path,
            'storage_disk' => 'local',
        ]);

        // Request to remove existing and add oversize image
        $oversizeFile = UploadedFile::fake()->create('oversize.jpg', 8200, 'image/jpeg');

        $response = $this->actingAs($this->activeUser)
            ->patch(route('kaizens.update', $kaizen), [
                'title' => 'New Title',
                'remove_attachment_ids' => [$attachment->id],
                'current_situation_images' => [$oversizeFile],
            ]);

        $response->assertInvalid(['current_situation_images.0']);

        // Check if DB and Storage are intact
        $this->assertDatabaseHas('kaizens', [
            'id' => $kaizen->id,
            'title' => 'Old Title',
        ]);

        $this->assertDatabaseHas('kaizen_attachments', [
            'id' => $attachment->id,
        ]);

        $this->assertTrue(Storage::disk('local')->exists($path));
    }

    public function test_effective_limit_uses_config_and_rejects_oversize()
    {
        Storage::fake('local');
        config(['kaizen.attachments.max_images_per_context' => 3]);

        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $this->activeUser->id,
            'status' => KaizenStatus::DRAFT,
        ]);

        KaizenAttachment::factory()->count(3)->create([
            'kaizen_id' => $kaizen->id,
            'context' => KaizenAttachmentContext::CURRENT_SITUATION,
        ]);

        $newImage = UploadedFile::fake()->create('new.jpg', 10, 'image/jpeg');

        $response = $this->actingAs($this->activeUser)
            ->patch(route('kaizens.update', $kaizen), [
                'title' => 'New Title',
                'current_situation' => 'Current situation details',
                'proposed_situation' => 'Proposed situation details',
                'current_situation_images' => [$newImage], // 3 existing + 1 new = 4 > 3
            ]);

        $response->assertInvalid(['current_situation_images']);
    }

    public function test_effective_limit_uses_config_and_accepts_when_removed()
    {
        Storage::fake('local');
        config(['kaizen.attachments.max_images_per_context' => 3]);

        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $this->activeUser->id,
            'status' => KaizenStatus::DRAFT,
            'category_id' => $this->category->id,
            'title' => 'Title',
            'current_situation' => 'Current situation details',
            'proposed_situation' => 'Proposed situation details',
        ]);

        $attachments = KaizenAttachment::factory()->count(3)->create([
            'kaizen_id' => $kaizen->id,
            'context' => KaizenAttachmentContext::CURRENT_SITUATION,
        ]);

        $newImage = UploadedFile::fake()->create('new.jpg', 10, 'image/jpeg');

        $response = $this->actingAs($this->activeUser)
            ->patch(route('kaizens.update', $kaizen), [
                'title' => 'New Title',
                'current_situation' => 'Current situation details',
                'proposed_situation' => 'Proposed situation details',
                'category_id' => $this->category->id,
                'remove_attachment_ids' => [$attachments->first()->id],
                'current_situation_images' => [$newImage], // 3 existing - 1 remove + 1 new = 3 <= 3
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(3, $kaizen->attachments()->count());
    }

    public function test_update_outer_transaction_rollback_cleans_new_physical_and_preserves_existing()
    {
        Storage::fake('local');

        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $this->activeUser->id,
            'status' => KaizenStatus::DRAFT,
            'category_id' => $this->category->id,
            'title' => 'Old Title',
            'current_situation' => 'Old Current',
            'proposed_situation' => 'Old Proposed',
        ]);

        $existingFile = UploadedFile::fake()->create('existing.jpg', 10, 'image/jpeg');
        $existingPath = $existingFile->store('kaizens/1/evidence/current', 'local');

        $attachment = KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizen->id,
            'context' => KaizenAttachmentContext::CURRENT_SITUATION,
            'storage_path' => $existingPath,
            'storage_disk' => 'local',
        ]);

        $newImage = UploadedFile::fake()->create('new.jpg', 10, 'image/jpeg');

        // Force a DB error to trigger an outer transaction rollback after attachment storage
        $mockAction = \Mockery::mock(UpdateKaizenDraft::class);
        $mockAction->shouldReceive('execute')->andThrow(new \Exception('Simulated core update failure.'));
        $this->app->instance(UpdateKaizenDraft::class, $mockAction);

        try {
            $this->actingAs($this->activeUser)
                ->withoutExceptionHandling()
                ->patch(route('kaizens.update', $kaizen), [
                    'title' => 'New Title',
                    'current_situation' => 'New Current',
                    'proposed_situation' => 'New Proposed',
                    'category_id' => $this->category->id,
                    'remove_attachment_ids' => [$attachment->id], // Mark existing for removal
                    'current_situation_images' => [$newImage], // Add new image
                ]);
        } catch (\Exception $e) {
            $this->assertEquals('Simulated core update failure.', $e->getMessage());
        }

        // DB state should be rolled back
        $this->assertDatabaseMissing('kaizen_attachments', [
            'original_name' => 'new.jpg',
        ]);

        $this->assertDatabaseHas('kaizen_attachments', [
            'id' => $attachment->id, // Removed attachment should still be there
        ]);

        // Storage state
        $this->assertTrue(Storage::disk('local')->exists($existingPath)); // Existing preserved

        $files = Storage::disk('local')->allFiles();
        // 1 file should exist: the existing file. The new file should have been cleaned up.
        $this->assertCount(1, $files);
        $this->assertEquals($existingPath, $files[0]);
    }

    public function test_update_outer_transaction_rollback_handles_throwable()
    {
        Storage::fake('local');

        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $this->activeUser->id,
            'status' => KaizenStatus::DRAFT,
            'category_id' => $this->category->id,
            'title' => 'Old Title',
            'current_situation' => 'Old Current',
            'proposed_situation' => 'Old Proposed',
        ]);

        $existingFile = UploadedFile::fake()->create('existing.jpg', 10, 'image/jpeg');
        $existingPath = $existingFile->store('kaizens/1/evidence/current', 'local');

        $attachment = KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizen->id,
            'context' => KaizenAttachmentContext::CURRENT_SITUATION,
            'storage_path' => $existingPath,
            'storage_disk' => 'local',
        ]);

        $newImage = UploadedFile::fake()->create('new.jpg', 10, 'image/jpeg');

        // Force a DB error to trigger an outer transaction rollback after attachment storage
        $mockAction = \Mockery::mock(UpdateKaizenDraft::class);
        $mockAction->shouldReceive('execute')->andThrow(new \Error('Simulated core update error.'));
        $this->app->instance(UpdateKaizenDraft::class, $mockAction);

        try {
            $this->actingAs($this->activeUser)
                ->withoutExceptionHandling()
                ->patch(route('kaizens.update', $kaizen), [
                    'title' => 'New Title',
                    'current_situation' => 'New Current',
                    'proposed_situation' => 'New Proposed',
                    'category_id' => $this->category->id,
                    'remove_attachment_ids' => [$attachment->id], // Mark existing for removal
                    'current_situation_images' => [$newImage], // Add new image
                ]);
        } catch (\Throwable $e) {
            $this->assertEquals('Simulated core update error.', $e->getMessage());
            $this->assertInstanceOf(\Error::class, $e);
        }

        // DB state should be rolled back
        $this->assertDatabaseMissing('kaizen_attachments', [
            'original_name' => 'new.jpg',
        ]);

        $this->assertDatabaseHas('kaizen_attachments', [
            'id' => $attachment->id, // Removed attachment should still be there
        ]);

        // Storage state
        $this->assertTrue(Storage::disk('local')->exists($existingPath)); // Existing preserved

        $files = Storage::disk('local')->allFiles();
        // 1 file should exist: the existing file. The new file should have been cleaned up.
        $this->assertCount(1, $files);
        $this->assertEquals($existingPath, $files[0]);
    }
}
