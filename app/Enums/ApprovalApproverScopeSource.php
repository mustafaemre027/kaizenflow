<?php

namespace App\Enums;

enum ApprovalApproverScopeSource: string
{
    case SYSTEM = 'SYSTEM';
    case KAIZEN_DEPARTMENT = 'KAIZEN_DEPARTMENT';
}
