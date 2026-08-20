<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AppendAuditLog
{
    public function execute(User $actor, Model $auditable, string $event, ?array $metadata = null): AuditLog
    {
        return AuditLog::create([
            'actor_user_id' => $actor->id,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'event' => $event,
            'metadata' => $metadata,
        ]);
    }
}
