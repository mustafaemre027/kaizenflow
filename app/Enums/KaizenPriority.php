<?php

namespace App\Enums;

enum KaizenPriority: string
{
    case LOW = 'LOW';
    case MEDIUM = 'MEDIUM';
    case HIGH = 'HIGH';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Düşük',
            self::MEDIUM => 'Orta',
            self::HIGH => 'Yüksek',
        };
    }
}
