<?php

namespace App\Enums;

enum KaizenStatus: string
{
    case DRAFT = 'DRAFT';
    case SUBMITTED = 'SUBMITTED';
    case REVISION_REQUESTED = 'REVISION_REQUESTED';
    case MANAGER_REVIEW = 'MANAGER_REVIEW';
    case APPROVED = 'APPROVED';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';
    case REJECTED = 'REJECTED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Taslak',
            self::SUBMITTED => 'Gönderildi',
            self::REVISION_REQUESTED => 'Revizyon İstendi',
            self::MANAGER_REVIEW => 'Yönetici İncelemesi',
            self::APPROVED => 'Onaylandı',
            self::IN_PROGRESS => 'Uygulamada',
            self::COMPLETED => 'Tamamlandı',
            self::REJECTED => 'Reddedildi',
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::COMPLETED, self::REJECTED => true,
            default => false,
        };
    }
}
