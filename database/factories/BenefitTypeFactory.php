<?php

namespace Database\Factories;

use App\Models\BenefitType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BenefitType>
 */
class BenefitTypeFactory extends Factory
{
    protected $model = BenefitType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'unit_label' => $this->faker->randomElement([null, 'saat', 'TL', '%', 'adet']),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
