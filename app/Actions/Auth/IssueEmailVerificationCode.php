<?php

namespace App\Actions\Auth;

use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use App\Support\OtpHashHelper;
use DomainException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class IssueEmailVerificationCode
{
    public function execute(User $user): void
    {
        // Fail-closed checks before any mutation
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

        // RateLimiter based on HMAC-SHA256 (no sensitive info in key)
        $rateLimiterKey = hash_hmac('sha256', 'resend-otp|'.$user->id, $appKey);

        if (RateLimiter::tooManyAttempts($rateLimiterKey, 1)) {
            throw new ThrottleRequestsException('Too many attempts.');
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $codeHash = OtpHashHelper::hash($user->id, $code);
        $expiresAt = now()->addMinutes(10);

        DB::transaction(function () use ($user, $codeHash, $expiresAt) {
            // Lock user first to maintain deterministic lock order
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            if (! $lockedUser) {
                throw new DomainException('User not found.');
            }

            // DB-level rate limiting fallback
            $existingCode = EmailVerificationCode::where('user_id', $user->id)->lockForUpdate()->first();
            if ($existingCode && $existingCode->updated_at && $existingCode->updated_at->addSeconds(60)->isFuture()) {
                throw new ThrottleRequestsException('Please wait before requesting a new code.');
            }

            EmailVerificationCode::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'code_hash' => $codeHash,
                    'attempts' => 0,
                    'expires_at' => $expiresAt,
                    // touch updated_at automatically
                ]
            );
        });

        // Hit the rate limiter
        RateLimiter::hit($rateLimiterKey, 60);

        // Synchronous notification AFTER successful commit
        $user->notify(new EmailVerificationCodeNotification($code));
    }
}
