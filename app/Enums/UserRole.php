<?php

namespace App\Enums;

enum UserRole: string
{
    case EMPLOYEE = 'EMPLOYEE';
    case OPEX_SPECIALIST = 'OPEX_SPECIALIST';
    case MANAGER = 'MANAGER';
    case ADMIN = 'ADMIN';

    public function label(): string
    {
        return match ($this) {
            self::EMPLOYEE => 'Çalışan',
            self::OPEX_SPECIALIST => 'OPEX Uzmanı',
            self::MANAGER => 'Yönetici',
            self::ADMIN => 'Sistem Yöneticisi',
        };
    }
}
