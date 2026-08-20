<?php

namespace App\Enums;

enum UserCapability: string
{
    case KAIZEN_IMPLEMENTATION_ASSIGN = 'kaizen.implementation.assign';
    case KAIZEN_IMPLEMENTATION_START = 'kaizen.implementation.start';
    case KAIZEN_IMPLEMENTATION_COMPLETE = 'kaizen.implementation.complete';
}
