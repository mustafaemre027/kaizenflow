<?php

namespace App\Actions\Settings\BenefitTypes;

use App\Models\BenefitType;

class SetBenefitTypeStatus
{
    public function execute(BenefitType $benefitType, bool $isActive): void
    {
        if ($benefitType->is_active === $isActive) {
            return;
        }

        $benefitType->is_active = $isActive;
        $benefitType->save();
    }
}
