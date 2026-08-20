<?php

namespace App\Enums;

enum WorkflowAction: string
{
    case START = 'START';
    case APPROVE = 'APPROVE';
    case REJECT = 'REJECT';
    case REQUEST_REVISION = 'REQUEST_REVISION';
    case RESUBMIT = 'RESUBMIT';

    public function label(): string
    {
        return match ($this) {
            self::START => 'Süreç Başlatıldı',
            self::APPROVE => 'Onaylandı',
            self::REJECT => 'Reddedildi',
            self::REQUEST_REVISION => 'Revizyon İstendi',
            self::RESUBMIT => 'Yeniden Gönderildi',
        };
    }

    /**
     * Returns the semantic CSS class suffix for badge rendering.
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::APPROVE => 'success',
            self::REJECT => 'danger',
            self::REQUEST_REVISION => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Review decision actions (reviewer-initiated, not creator-initiated).
     *
     * @return array<self>
     */
    public static function reviewActions(): array
    {
        return [self::APPROVE, self::REJECT, self::REQUEST_REVISION];
    }
}
