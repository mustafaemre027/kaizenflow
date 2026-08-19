<?php

namespace Tests\Feature\Http\Controllers;

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

class KaizenAttachmentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $activeUser;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create(['is_active' => true]);
        $this->activeUser = User::factory()->create([
            'is_active' => true,
            'department_id' => $this->department->id,
        ]);
        Category::factory()->create(['is_active' => true]);
    }

    public function test_authorized_user_can_view_attachment_binary()
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('test.jpg', 10, 'image/jpeg');
        $path = $file->store('kaizens/1/evidence', 'local');

        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $this->activeUser->id,
        ]);

        $attachment = KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizen->id,
            'storage_path' => $path,
            'storage_disk' => 'local',
            'mime_type' => 'image/jpeg',
        ]);

        $response = $this->actingAs($this->activeUser)
            ->get(route('kaizens.attachments.show', [$kaizen, $attachment]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_unauthorized_user_cannot_view_attachment_binary()
    {
        Storage::fake('local');
        $path = UploadedFile::fake()->create('test.jpg', 10, 'image/jpeg')->store('kaizens/1/evidence', 'local');

        $otherDepartment = Department::factory()->create(['is_active' => true]);
        $otherUser = User::factory()->create([
            'is_active' => true,
            'department_id' => $otherDepartment->id,
            'role' => UserRole::EMPLOYEE,
        ]);

        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $this->activeUser->id,
            'department_id' => $this->activeUser->department_id,
        ]);

        $attachment = KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizen->id,
            'storage_path' => $path,
            'storage_disk' => 'local',
        ]);

        $response = $this->actingAs($otherUser)
            ->get(route('kaizens.attachments.show', [$kaizen, $attachment]));

        $response->assertForbidden();
    }

    public function test_attachment_view_fails_if_parent_kaizen_does_not_match()
    {
        Storage::fake('local');
        $path = UploadedFile::fake()->create('test.jpg', 10, 'image/jpeg')->store('kaizens/1/evidence', 'local');

        $kaizen1 = Kaizen::factory()->create(['creator_user_id' => $this->activeUser->id]);
        $kaizen2 = Kaizen::factory()->create(['creator_user_id' => $this->activeUser->id]);

        $attachment = KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizen2->id, // belongs to kaizen 2
            'storage_path' => $path,
            'storage_disk' => 'local',
        ]);

        // Attempting to access via kaizen 1 URL
        $response = $this->actingAs($this->activeUser)
            ->get(route('kaizens.attachments.show', [$kaizen1, $attachment]));

        $response->assertNotFound();
    }

    public function test_missing_physical_file_returns_404()
    {
        $kaizen = Kaizen::factory()->create(['creator_user_id' => $this->activeUser->id]);

        $attachment = KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizen->id,
            'storage_path' => 'kaizens/missing/file.jpg',
            'storage_disk' => 'local',
        ]);

        $response = $this->actingAs($this->activeUser)
            ->get(route('kaizens.attachments.show', [$kaizen, $attachment]));

        $response->assertNotFound();
    }

    public function test_unsupported_mime_type_metadata_fails_closed()
    {
        Storage::fake('local');
        // create a fake txt file representing malicious injection or broken metadata
        $file = UploadedFile::fake()->create('test.txt', 10, 'text/plain');
        $path = $file->store('kaizens/1/evidence', 'local');

        $kaizen = Kaizen::factory()->create(['creator_user_id' => $this->activeUser->id]);

        $attachment = KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizen->id,
            'storage_path' => $path,
            'storage_disk' => 'local',
            'mime_type' => 'text/plain',
        ]);

        $response = $this->actingAs($this->activeUser)
            ->get(route('kaizens.attachments.show', [$kaizen, $attachment]));

        $response->assertNotFound();
    }

    public function test_authorized_user_can_download_attachment()
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('test.jpg', 10, 'image/jpeg');
        $path = $file->store('kaizens/1/evidence', 'local');

        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $this->activeUser->id,
        ]);

        $attachment = KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizen->id,
            'storage_path' => $path,
            'storage_disk' => 'local',
            'mime_type' => 'image/jpeg',
            'original_name' => 'Original Photo.jpg',
        ]);

        $response = $this->actingAs($this->activeUser)
            ->get(route('kaizens.attachments.download', [$kaizen, $attachment]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Disposition', 'attachment; filename="Original Photo.jpg"');
    }

    public function test_unauthorized_user_cannot_download_attachment()
    {
        Storage::fake('local');
        $path = UploadedFile::fake()->create('test.jpg', 10, 'image/jpeg')->store('kaizens/1/evidence', 'local');

        $otherDepartment = Department::factory()->create(['is_active' => true]);
        $otherUser = User::factory()->create([
            'is_active' => true,
            'department_id' => $otherDepartment->id,
            'role' => UserRole::EMPLOYEE,
        ]);

        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $this->activeUser->id,
            'department_id' => $this->activeUser->department_id,
        ]);

        $attachment = KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizen->id,
            'storage_path' => $path,
            'storage_disk' => 'local',
        ]);

        $response = $this->actingAs($otherUser)
            ->get(route('kaizens.attachments.download', [$kaizen, $attachment]));

        $response->assertForbidden();
    }

    public function test_download_fails_if_parent_kaizen_does_not_match()
    {
        Storage::fake('local');
        $path = UploadedFile::fake()->create('test.jpg', 10, 'image/jpeg')->store('kaizens/1/evidence', 'local');

        $kaizen1 = Kaizen::factory()->create(['creator_user_id' => $this->activeUser->id]);
        $kaizen2 = Kaizen::factory()->create(['creator_user_id' => $this->activeUser->id]);

        $attachment = KaizenAttachment::factory()->create([
            'kaizen_id' => $kaizen2->id, // belongs to kaizen 2
            'storage_path' => $path,
            'storage_disk' => 'local',
        ]);

        // Attempting to access via kaizen 1 URL
        $response = $this->actingAs($this->activeUser)
            ->get(route('kaizens.attachments.download', [$kaizen1, $attachment]));

        $response->assertNotFound();
    }
}
