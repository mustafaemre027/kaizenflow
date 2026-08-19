<?php

namespace App\Enums;

enum WorkflowAction: string
{
    case START = 'START';
    case APPROVE = 'APPROVE';
    case REJECT = 'REJECT';
    case REQUEST_REVISION = 'REQUEST_REVISION';
    case RESUBMIT = 'RESUBMIT';
}
