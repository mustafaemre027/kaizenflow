<?php

namespace App\Support;

class OtpHashHelper
{
    /**
     * Generate HMAC-SHA256 hash for the OTP based on user context.
     *
     * @throws \DomainException if APP_KEY is missing
     */
    public static function hash(int $userId, string $otp): string
    {
        $appKey = config('app.key');

        if (empty($appKey)) {
            throw new \DomainException('Application key is missing.');
        }

        $context = "email-verification|{$userId}|{$otp}";

        return hash_hmac('sha256', $context, $appKey);
    }
}
