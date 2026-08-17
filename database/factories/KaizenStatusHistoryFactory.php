<?php

namespace Database\Factories;

use App\Enums\KaizenStatus;
use App\Models\Kaizen;
use App\Models\KaizenStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KaizenStatusHistory>
 */
class KaizenStatusHistoryFactory extends Factory
{
    protected $model = KaizenStatusHistory::class;

    public function definition(): array
    {
        return [
            'kaizen_id' => Kaizen::factory(),
            'actor_user_id' => User::factory(),
            'transition_code' => 'TR-001',
            'from_status' => KaizenStatus::DRAFT->value,
            'to_status' => KaizenStatus::SUBMITTED->value,
            'reason' => $this->faker->optional()->sentence(),
            'metadata' => $this->faker->optional()->randomElement([
                null,
                ['ip' => $this->faker->ipv4()],
                ['browser' => $this->faker->userAgent()],
            ]),
        ];
    }
}
