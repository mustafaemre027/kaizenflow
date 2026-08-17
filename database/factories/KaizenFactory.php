<?php

namespace Database\Factories;

use App\Enums\KaizenPriority;
use App\Enums\KaizenStatus;
use App\Models\Category;
use App\Models\Department;
use App\Models\Kaizen;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kaizen>
 */
class KaizenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'KF-TEST-'.$this->faker->unique()->numberBetween(10000, 99999),
            'creator_user_id' => User::factory(),
            'department_id' => Department::factory(),
            'category_id' => Category::factory(),
            'assigned_user_id' => null,
            'title' => $this->faker->sentence(),
            'current_situation' => $this->faker->paragraph(),
            'proposed_situation' => $this->faker->paragraph(),
            'expected_benefit' => $this->faker->paragraph(),
            'actual_result' => null,
            'realized_benefit' => null,
            'status' => KaizenStatus::DRAFT,
            'priority' => null,
            'target_date' => null,
            'submitted_at' => null,
            'approved_at' => null,
            'started_at' => null,
            'completed_at' => null,
            'rejected_at' => null,
        ];
    }

    public function withPriority(KaizenPriority $priority): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $priority,
        ]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_user_id' => $user->id,
        ]);
    }

    public function withStatus(KaizenStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
