<?php

namespace App\Actions\Settings\BenefitTypes;

use App\Models\BenefitType;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UpdateBenefitType
{
    public function execute(BenefitType $benefitType, array $data): BenefitType
    {
        $name = trim($data['name']);

        if (empty($name)) {
            throw ValidationException::withMessages([
                'name' => 'Fayda türü adı boş olamaz.',
            ]);
        }

        $canonicalName = Str::lower($name);

        if (BenefitType::whereRaw('LOWER(name) = ?', [$canonicalName])
            ->where('id', '!=', $benefitType->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Bu isimde bir fayda türü zaten mevcut.',
            ]);
        }

        $unitLabel = null;
        if (isset($data['unit_label']) && trim($data['unit_label']) !== '') {
            $unitLabel = trim($data['unit_label']);
        }

        $benefitType->update([
            'name' => $name,
            'unit_label' => $unitLabel,
        ]);

        return $benefitType;
    }
}
