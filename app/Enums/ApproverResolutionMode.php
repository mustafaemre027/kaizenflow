<?php

namespace App\Enums;

enum ApproverResolutionMode: string
{
    case LEGACY_GROUP = 'LEGACY_GROUP';
    case CAPABILITY_RULE = 'CAPABILITY_RULE';
}
