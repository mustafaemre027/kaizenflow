<?php

namespace App\Actions\Settings\BenefitTypes;

use App\Models\BenefitType;

class ToggleBenefitTypeStatus
{
    public function execute(BenefitType $benefitType): BenefitType
    {
        $benefitType->update([
            'is_active' => ! $benefitType->is_active,
        ]);

        return $benefitType;
    }
}
