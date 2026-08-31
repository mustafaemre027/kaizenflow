<?php

namespace App\Actions\Users;

use App\Enums\UserCapability;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\UserCapabilityResolver;
use DomainException;
use Illuminate\Support\Facades\Password;

class SendUserInvitation
{
    public function __construct(
        private UserCapabilityResolver $resolver
    ) {}

    public function execute(User $actor, User $target): string
    {
        // Fail closed checks
        if (! $actor->is_active) {
            throw new DomainException('Actor is not active.');
        }

        if (! $this->resolver->allowsSystem($actor, UserCapability::AUTHORIZATION_MANAGE)) {
            throw new DomainException('Actor does not have authorization to manage users.');
        }

        if ($actor->id === $target->id) {
            throw new DomainException('Cannot send an invitation to yourself.');
        }

        if (! $target->is_active) {
            throw new DomainException('Target user is not active.');
        }

        if (! $target->must_set_password) {
            throw new DomainException('Target user does not need an invitation.');
        }

        $status = Password::broker()->sendResetLink(
            ['email' => $target->email]
        );

        if ($status === Password::RESET_LINK_SENT) {
            $target->invitation_sent_at = now();
            $target->save();

            $this->writeAudit($actor, $target);
        }

        return $status;
    }

    protected function writeAudit(User $actor, User $target): void
    {
        $audit = new AuditLog([
            'actor_user_id' => $actor->id,
            'auditable_type' => User::class,
            'auditable_id' => $target->id,
            'event' => 'user.invitation_sent',
            'metadata' => [
                'target_user_id' => $target->id,
                'source' => 'user_management',
            ],
        ]);

        $audit->save();
    }
}
