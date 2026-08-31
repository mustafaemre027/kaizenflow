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
use Illuminate\Database\QueryException;
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

        try {
            $result = DB::transaction(function () use ($actor, $target, $data) {
                // Lock target
                $lockedTarget = User::where('id', $target->id)->lockForUpdate()->first();
                if (! $lockedTarget) {
                    throw new DomainException('Target user not found.');
                }

                // Department Change Protection
                $newDepartmentId = isset($data['department_id']) ? (int) $data['department_id'] : null;
                $oldDepartmentId = $lockedTarget->department_id !== null ? (int) $lockedTarget->department_id : null;
                if ($oldDepartmentId !== $newDepartmentId) {
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
                    $currentValue = $lockedTarget->$field;

                    if ($field === 'role') {
                        $newValue = UserRole::tryFrom($newValue);
                    } elseif ($field === 'department_id') {
                        $newValue = $newValue !== null ? (int) $newValue : null;
                        $currentValue = $currentValue !== null ? (int) $currentValue : null;
                    }

                    if ($newValue !== $currentValue) {
                        $changedFields[] = $field;
                        $lockedTarget->$field = $newValue;
                    }
                }

                if ($emailChanged) {
                    $existing = User::where('id', '!=', $lockedTarget->id)
                        ->whereRaw('LOWER(email) = ?', [$newEmail])
                        ->exists();

                    if ($existing) {
                        throw new DomainException('Bu e-posta adresi ile kayıtlı bir kullanıcı zaten mevcut.');
                    }

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
                        'email_changed' => false,
                    ];
                }

                $lockedTarget->save();

                $this->writeAudit($actor, $lockedTarget, $changedFields);

                return [
                    'success' => true,
                    'email_changed' => $emailChanged,
                    'must_set_password' => $lockedTarget->must_set_password,
                    'is_active' => $lockedTarget->is_active,
                    'target_id' => $lockedTarget->id,
                ];
            });

        } catch (QueryException $e) {
            $errorCode = $e->errorInfo[1] ?? null;
            if ($errorCode === 1062 || $errorCode === 19) { // 1062 MySQL, 19 SQLite
                throw new DomainException('Bu e-posta adresi ile kayıtlı bir kullanıcı zaten mevcut.');
            }
            throw $e;
        }

        if (isset($result['success']) && $result['success']) {
            if (! empty($result['email_changed'])) {
                $freshTarget = clone $target;
                $freshTarget->id = $result['target_id'];
                // We should re-fetch to ensure relations/etc are clean if needed, but for mail sending mostly email/status are used.
                // Let's refetch to be absolutely safe.
                $freshTarget = User::find($result['target_id']);

                $mailFailureWarning = null;

                if ($result['must_set_password']) {
                    try {
                        $this->sendUserInvitation->execute($actor, $freshTarget);
                    } catch (Exception $e) {
                        $mailFailureWarning = 'Kullanıcı güncellendi ancak yeni adrese davet e-postası gönderilemedi.';
                    }
                } else {
                    try {
                        if ($result['is_active']) {
                            $this->issueEmailVerificationCode->execute($freshTarget);
                        }
                    } catch (Exception $e) {
                        $mailFailureWarning = 'Kullanıcı güncellendi ancak doğrulama e-postası gönderilemedi.';
                    }
                }

                return [
                    'success' => true,
                    'message' => $mailFailureWarning ?? 'Kullanıcı başarıyla güncellendi.',
                ];
            }

            return [
                'success' => true,
                'message' => $result['message'] ?? 'Kullanıcı başarıyla güncellendi.',
            ];
        }

        return $result;
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
