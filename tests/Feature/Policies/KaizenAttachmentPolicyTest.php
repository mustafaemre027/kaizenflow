<?php

namespace Tests\Feature\Policies;

use App\Enums\KaizenStatus;
use App\Models\Kaizen;
use App\Models\KaizenAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KaizenAttachmentPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_viewer_can_view_attachment(): void
    {
        $creator = User::factory()->create();
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $creator->id,
            'status' => KaizenStatus::SUBMITTED,
        ]);
        $attachment = KaizenAttachment::factory()->create(['kaizen_id' => $kaizen->id]);

        $this->assertTrue($creator->can('view', $attachment));
    }

    public function test_unauthorized_user_cannot_view_attachment(): void
    {
        $creator = User::factory()->create();
        $unrelated = User::factory()->create();
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $creator->id,
            'status' => KaizenStatus::SUBMITTED,
        ]);
        $attachment = KaizenAttachment::factory()->create(['kaizen_id' => $kaizen->id]);

        $this->assertFalse($unrelated->can('view', $attachment));
    }

    public function test_creator_can_create_and_delete_attachment_in_editable_status(): void
    {
        $creator = User::factory()->create();
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $creator->id,
            'status' => KaizenStatus::DRAFT,
        ]);
        $attachment = KaizenAttachment::factory()->create(['kaizen_id' => $kaizen->id]);

        $this->assertTrue($creator->can('create', [KaizenAttachment::class, $kaizen]));
        $this->assertTrue($creator->can('delete', $attachment));
    }

    public function test_creator_cannot_create_and_delete_attachment_in_non_editable_status(): void
    {
        $creator = User::factory()->create();
        $kaizen = Kaizen::factory()->create([
            'creator_user_id' => $creator->id,
            'status' => KaizenStatus::SUBMITTED,
        ]);
        $attachment = KaizenAttachment::factory()->create(['kaizen_id' => $kaizen->id]);

        $this->assertFalse($creator->can('create', [KaizenAttachment::class, $kaizen]));
        $this->assertFalse($creator->can('delete', $attachment));
    }
}
