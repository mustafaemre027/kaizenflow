<?php

namespace Database\Factories;

use App\Models\BenefitType;
use App\Models\Kaizen;
use App\Models\KaizenBenefit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KaizenBenefit>
 */
class KaizenBenefitFactory extends Factory
{
    protected $model = KaizenBenefit::class;

    public function definition(): array
    {
        return [
            'kaizen_id' => Kaizen::factory(),
            'benefit_type_id' => BenefitType::factory(),
            'expected_value' => null,
            'expected_note' => null,
            'realized_value' => null,
            'realized_note' => null,
        ];
    }

    public function withExpected(string $value = '100.0000', ?string $note = null): static
    {
        return $this->state(fn (array $attributes) => [
            'expected_value' => $value,
            'expected_note' => $note,
        ]);
    }

    public function withRealized(string $value = '95.0000', ?string $note = null): static
    {
        return $this->state(fn (array $attributes) => [
            'realized_value' => $value,
            'realized_note' => $note,
        ]);
    }
}
