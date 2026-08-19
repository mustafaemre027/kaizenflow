<?php

namespace Database\Factories;

use App\Enums\KaizenAttachmentContext;
use App\Models\Kaizen;
use App\Models\KaizenAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KaizenAttachment>
 */
class KaizenAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kaizen_id' => Kaizen::factory(),
            'uploaded_by_user_id' => User::factory(),
            'context' => KaizenAttachmentContext::CURRENT_SITUATION,
            'original_name' => 'test-image.jpg',
            'storage_disk' => 'local',
            'storage_path' => 'kaizens/1/evidence/current_situation/01J00000000000000000000000.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
            'sha256' => hash('sha256', 'testcontent'),
            'sort_order' => 1,
        ];
    }
}
