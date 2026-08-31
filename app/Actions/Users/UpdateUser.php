<?php

namespace App\Actions\Users;

use App\Actions\Auth\IssueEmailVerificationCode;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Models\UserCapabilityGrant;
use DomainException;
use Exception;
use Illuminate\Support\Facades\DB;

class UpdateUser
{
    public function __construct(
        private IssueEmailVerificationCode $issueEmailVerificationCode,
        private SendUserInvitation $sendUserInvitation
    ) {}

    public function execute(User $actor, User $target, array $data): array
    {
        if (! $actor->is_active) {
            throw new DomainException('Actor is not active.');
        }

        if ($actor->id === $target->id) {
            throw new DomainException('Cannot update your own profile from user management.');
        }

        return DB::transaction(function () use ($actor, $target, $data) {
            $freshTarget = clone $target;
            // Lock target
            $lockedTarget = User::where('id', $target->id)->lockForUpdate()->first();
            if (! $lockedTarget) {
                throw new DomainException('Target user not found.');
            }

            // Department Change Protection
            $newDepartmentId = $data['department_id'] ?? null;
            if ($lockedTarget->department_id !== $newDepartmentId) {
                $hasActiveDepartmentGrants = UserCapabilityGrant::where('user_id', $lockedTarget->id)
                    ->where('is_active', true)
                    ->exists();

                if ($hasActiveDepartmentGrants) {
                    throw new DomainException('Kullanıcının aktif departman yetkileri bulunduğu için departman değiştirilemez. Önce ilgili yetkileri düzenleyin.');
                }
            }

            $oldEmail = $lockedTarget->email;
            $newEmail = strtolower(trim($data['email']));
            $emailChanged = $oldEmail !== $newEmail;

            $changedFields = [];
            foreach (['name', 'role', 'department_id'] as $field) {
                $newValue = $data[$field] ?? null;
                if ($field === 'role') {
                    $newValue = UserRole::tryFrom($newValue);
                }

                if ($newValue !== $lockedTarget->$field) {
                    $changedFields[] = $field;
                    $lockedTarget->$field = $newValue;
                }
            }

            $mailFailureWarning = null;

            if ($emailChanged) {
                $changedFields[] = 'email';
                $lockedTarget->email = $newEmail;

                // Invalidate tokens for old and new email
                DB::table('password_reset_tokens')->whereIn('email', [$oldEmail, $newEmail])->delete();
                EmailVerificationCode::where('user_id', $lockedTarget->id)->delete();

                if ($lockedTarget->must_set_password) {
                    // Pending User Email Change
                    $lockedTarget->invitation_sent_at = null;
                } else {
                    // Ready User Email Change
                    $lockedTarget->email_verified_at = null;
                }
            }

            if (empty($changedFields)) {
                return [
                    'success' => true,
                    'message' => 'Değişiklik yapılmadı.',
                ];
            }

            $lockedTarget->save();

            if ($emailChanged) {
                if ($lockedTarget->must_set_password) {
                    try {
                        $this->sendUserInvitation->execute($actor, $lockedTarget);
                    } catch (Exception $e) {
                        $mailFailureWarning = 'Kullanıcı güncellendi ancak yeni adrese davet e-postası gönderilemedi.';
                    }
                } else {
                    try {
                        if ($lockedTarget->is_active) {
                            $this->issueEmailVerificationCode->execute($lockedTarget);
                        }
                    } catch (Exception $e) {
                        $mailFailureWarning = 'Kullanıcı güncellendi ancak doğrulama e-postası gönderilemedi.';
                    }
                }
            }

            $this->writeAudit($actor, $lockedTarget, $changedFields);

            return [
                'success' => true,
                'message' => $mailFailureWarning ?? 'Kullanıcı başarıyla güncellendi.',
            ];
        });
    }

    protected function writeAudit(User $actor, User $target, array $changedFields): void
    {
        $safeFields = array_filter($changedFields, fn ($field) => $field !== 'email');

        if (empty($safeFields) && in_array('email', $changedFields)) {
            $safeFields = ['email_changed']; // Mask actual email in log but indicate change
        }

        $audit = new AuditLog([
            'actor_user_id' => $actor->id,
            'auditable_type' => User::class,
            'auditable_id' => $target->id,
            'event' => 'user.updated',
            'metadata' => [
                'target_user_id' => $target->id,
                'changed_fields' => array_values($safeFields),
            ],
        ]);

        $audit->save();
    }
}
