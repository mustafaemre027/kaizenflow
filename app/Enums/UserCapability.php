<?php

namespace App\Enums;

enum UserCapability: string
{
    case KAIZEN_IMPLEMENTATION_ASSIGN = 'kaizen.implementation.assign';
    case KAIZEN_IMPLEMENTATION_START = 'kaizen.implementation.start';
    case KAIZEN_IMPLEMENTATION_COMPLETE = 'kaizen.implementation.complete';

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
            self::KAIZEN_IMPLEMENTATION_COMPLETE => CapabilityScope::DEPARTMENT,

            self::ORGANIZATION_VIEW,
            self::ORGANIZATION_MANAGE,
            self::APPROVAL_CONFIGURATION_VIEW,
            self::APPROVAL_CONFIGURATION_MANAGE,
            self::AUTHORIZATION_MANAGE => CapabilityScope::SYSTEM,
        };
    }
}
