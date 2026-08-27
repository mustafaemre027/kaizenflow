<?php

namespace App\Actions\Auth;

use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Support\OtpHashHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class VerifyEmailVerificationCode
{
    public function execute(User $user, string $code): void
    {
        $appKey = config('app.key');
        if (empty($appKey)) {
            throw new \Exception('Application key is missing.');
        }

        if (! $user->is_active) {
            throw new DomainException('User is not active.');
        }

        if ($user->hasVerifiedEmail()) {
            throw new DomainException('Email is already verified.');
        }

        $inputHash = OtpHashHelper::hash($user->id, $code);

        $error = null;

        DB::transaction(function () use ($user, $inputHash, &$error) {
            // Lock order: User -> EmailVerificationCode
            User::where('id', $user->id)->lockForUpdate()->first();
            $record = EmailVerificationCode::where('user_id', $user->id)->lockForUpdate()->first();

            if (! $record) {
                $error = new DomainException('Verification code not found or already used.');

                return;
            }

            if ($record->attempts >= 5) {
                $error = new DomainException('Too many failed attempts. Code is permanently invalid.');

                return;
            }

            if ($record->expires_at->isPast() || $record->expires_at->equalTo(now())) {
                $error = new DomainException('Verification code has expired.');

                return;
            }

            if (! hash_equals($record->code_hash, $inputHash)) {
                $record->increment('attempts');
                $error = new DomainException('Invalid verification code.');

                return;
            }

            // Success
            $user->email_verified_at = now();
            $user->save();
            $record->delete();
        });

        if ($error !== null) {
            throw $error;
        }
    }
}
