<?php

namespace App\Actions\Authorization;

use App\Enums\UserCapability;
use App\Models\User;

class RevokeSystemCapability
{
    public function execute(User $actor, User $target, UserCapability $capability): void
    {
        // Stub for TDD RED state
    }
}
