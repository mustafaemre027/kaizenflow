<?php

namespace App\Services;

use App\Models\Kaizen;
use LogicException;

class KaizenCodeGenerator
{
    public function generate(Kaizen $kaizen): string
    {
        if (! $kaizen->id || ! $kaizen->created_at) {
            throw new LogicException('Cannot generate code for an unsaved Kaizen model or a model without created_at timestamp.');
        }

        $year = $kaizen->created_at->format('Y');
        $paddedId = str_pad((string) $kaizen->id, 6, '0', STR_PAD_LEFT);

        return sprintf('KZN-%s-%s', $year, $paddedId);
    }
}
