<?php

namespace App\Exceptions;

use LogicException;

class LastAuthorizationManagerException extends LogicException
{
    public function __construct(string $message = 'Cannot revoke the last active authorization manager.')
    {
        parent::__construct($message);
    }
}
