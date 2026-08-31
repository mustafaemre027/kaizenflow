<?php

namespace App\Actions\Users;

use App\Models\AuditLog;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateUserWithInvitation
{
    public function __construct(
        private SendUserInvitation $sendUserInvitation
    ) {}

    public function execute(User $actor, array $validatedPayload): array
    {
        $email = $validatedPayload['email'];

        // Prevent race condition duplicate
        $existing = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
        if ($existing) {
            throw new DomainException('Bu e-posta adresi ile kayıtlı bir kullanıcı zaten mevcut.');
        }

        $user = DB::transaction(function () use ($actor, $validatedPayload) {
            // Generate a secure placeholder that will never be used/seen
            $placeholder = Str::random(64);

            $user = new User();
            $user->name = $validatedPayload['name'];
            $user->email = $validatedPayload['email'];
            $user->role = $validatedPayload['role'];
            $user->department_id = $validatedPayload['department_id'] ?? null;

            // Server-controlled fields
            $user->is_active = true;
            $user->must_set_password = true;
            $user->email_verified_at = null;
            $user->invitation_sent_at = null;
            $user->password = Hash::make($placeholder); // hashed cast in Model will happen if we just assign, but we use Hash::make to be safe, assuming Model casts 'password' to 'hashed'.

            $user->save();

            $this->writeCreatedAudit($actor, $user);

            return $user;
        });

        return $this->orchestrateInvitation($actor, $user);
    }

    protected function writeCreatedAudit(User $actor, User $target): void
    {
        $audit = new AuditLog([
            'actor_user_id' => $actor->id,
            'auditable_type' => User::class,
            'auditable_id' => $target->id,
            'event' => 'user.created',
            'metadata' => [
                'target_user_id' => $target->id,
                'role' => $target->role->value,
                'department_id' => $target->department_id,
                'is_active' => $target->is_active,
                'must_set_password' => $target->must_set_password,
                'source' => 'user_management',
            ],
        ]);

        $audit->save();
    }

    protected function orchestrateInvitation(User $actor, User $target): array
    {
        try {
            $status = $this->sendUserInvitation->execute($actor, $target);

            if ($status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT) {
                return [
                    'success' => true,
                    'message' => 'Kullanıcı başarıyla oluşturuldu ve davet e-postası gönderildi.',
                ];
            }

            return [
                'success' => false, // User created, but invite failed
                'message' => 'Kullanıcı oluşturuldu ancak davet gönderilemedi ('.trans($status).'). Davet daha sonra yeniden gönderilebilir.',
            ];
        } catch (\Exception $e) {
            // Catch mailer exceptions
            return [
                'success' => false,
                'message' => 'Kullanıcı oluşturuldu ancak davet gönderilemedi. Davet daha sonra yeniden gönderilebilir.',
            ];
        }
    }
}
