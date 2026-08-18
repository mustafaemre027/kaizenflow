<?php

namespace App\Exceptions;

use App\Enums\KaizenStatus;
use Exception;

class InvalidKaizenTransition extends Exception
{
    public readonly KaizenStatus $from;

    public readonly KaizenStatus $to;

    public function __construct(KaizenStatus $from, KaizenStatus $to)
    {
        $this->from = $from;
        $this->to = $to;

        parent::__construct(
            sprintf('Invalid Kaizen transition from %s to %s.', $from->value, $to->value)
        );
    }
}
