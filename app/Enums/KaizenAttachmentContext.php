<?php

namespace App\Enums;

enum KaizenAttachmentContext: string
{
    case CURRENT_SITUATION = 'current_situation';
    case PROPOSED_SITUATION = 'proposed_situation';

    public function label(): string
    {
        return match ($this) {
            self::CURRENT_SITUATION => 'Mevcut Durum',
            self::PROPOSED_SITUATION => 'Önerilen Durum',
        };
    }
}
