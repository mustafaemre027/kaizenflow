<?php

namespace App\Actions\Settings\BenefitTypes;

use App\Models\BenefitType;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateBenefitType
{
    public function execute(array $data): BenefitType
    {
        $name = trim($data['name']);

        if (empty($name)) {
            throw ValidationException::withMessages([
                'name' => 'Fayda türü adı boş olamaz.',
            ]);
        }

        $canonicalName = Str::lower($name);

        if (BenefitType::whereRaw('LOWER(name) = ?', [$canonicalName])->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Bu isimde bir fayda türü zaten mevcut.',
            ]);
        }

        $unitLabel = null;
        if (isset($data['unit_label']) && trim($data['unit_label']) !== '') {
            $unitLabel = trim($data['unit_label']);
        }

        return BenefitType::create([
            'name' => $name,
            'unit_label' => $unitLabel,
            'is_active' => true,
        ]);
    }
}
