<?php

namespace App\Enums;

enum UserCapability: string
{
    case KAIZEN_IMPLEMENTATION_ASSIGN = 'kaizen.implementation.assign';
    case KAIZEN_IMPLEMENTATION_START = 'kaizen.implementation.start';
    case KAIZEN_IMPLEMENTATION_COMPLETE = 'kaizen.implementation.complete';

    case KAIZEN_OPEX_REVIEW = 'kaizen.opex_review';
    case KAIZEN_DEPARTMENT_APPROVE = 'kaizen.department_approve';
    case KAIZEN_BOARD_APPROVE = 'kaizen.board_approve';

    case ORGANIZATION_VIEW = 'organization.view';
    case ORGANIZATION_MANAGE = 'organization.manage';
    case APPROVAL_CONFIGURATION_VIEW = 'approval_configuration.view';
    case APPROVAL_CONFIGURATION_MANAGE = 'approval_configuration.manage';
    case AUTHORIZATION_MANAGE = 'authorization.manage';

    public function scope(): CapabilityScope
    {
        return match ($this) {
            self::KAIZEN_IMPLEMENTATION_ASSIGN,
            self::KAIZEN_IMPLEMENTATION_START,
            self::KAIZEN_IMPLEMENTATION_COMPLETE,
            self::KAIZEN_DEPARTMENT_APPROVE => CapabilityScope::DEPARTMENT,

            self::KAIZEN_OPEX_REVIEW,
            self::KAIZEN_BOARD_APPROVE,
            self::ORGANIZATION_VIEW,
            self::ORGANIZATION_MANAGE,
            self::APPROVAL_CONFIGURATION_VIEW,
            self::APPROVAL_CONFIGURATION_MANAGE,
            self::AUTHORIZATION_MANAGE => CapabilityScope::SYSTEM,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::KAIZEN_IMPLEMENTATION_ASSIGN => 'Uygulama Sorumlusu Atama',
            self::KAIZEN_IMPLEMENTATION_START => 'Uygulamayı Başlatma',
            self::KAIZEN_IMPLEMENTATION_COMPLETE => 'Uygulamayı Tamamlama',
            self::KAIZEN_DEPARTMENT_APPROVE => 'Departman Onayı',

            self::KAIZEN_OPEX_REVIEW => 'Sürekli İyileştirme İncelemesi',
            self::KAIZEN_BOARD_APPROVE => 'Kurul Onayı',
            self::ORGANIZATION_VIEW => 'Organizasyonu Görüntüleme',
            self::ORGANIZATION_MANAGE => 'Organizasyonu Yönetme',
            self::APPROVAL_CONFIGURATION_VIEW => 'Onay Yapılandırmasını Görüntüleme',
            self::APPROVAL_CONFIGURATION_MANAGE => 'Onay Yapılandırmasını Yönetme',
            self::AUTHORIZATION_MANAGE => 'Yetkilendirme Yönetimi',
        };
    }
}
