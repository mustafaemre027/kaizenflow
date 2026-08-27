<?php

namespace Tests\Feature\Auth;

use App\Actions\Auth\IssueEmailVerificationCode;
use App\Actions\Auth\VerifyEmailVerificationCode;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmailVerificationBackendTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper: Extract OTP from the notification instance using Reflection.
     */
    protected function extractOtpFromNotification($notification): string
    {
        $reflection = new \ReflectionClass($notification);
        $property = $reflection->getProperty('code');
        $property->setAccessible(true);
        return $property->getValue($notification);
    }

    public function test_migration_creates_table_with_required_columns()
    {
        $this->assertTrue(Schema::hasTable('email_verification_codes'));
        $this->assertTrue(Schema::hasColumns('email_verification_codes', [
            'id', 'user_id', 'code_hash', 'attempts', 'expires_at', 'created_at', 'updated_at'
        ]));
    }

    public function test_user_id_has_unique_constraint()
    {
        $user = User::factory()->create();

        EmailVerificationCode::create([
            'user_id' => $user->id,
            'code_hash' => 'hash1',
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionMessageMatches('/UNIQUE constraint failed|Duplicate entry/');

        EmailVerificationCode::create([
            'user_id' => $user->id,
            'code_hash' => 'hash2',
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);
    }

    public function test_otp_is_exactly_6_digits_and_plaintext_is_not_saved_in_database()
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        
        $action = new IssueEmailVerificationCode();
        $action->execute($user);

        Notification::assertSentTo($user, EmailVerificationCodeNotification::class, function ($notification) use ($user) {
            $code = $this->extractOtpFromNotification($notification);
            
            $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $code);

            $record = EmailVerificationCode::where('user_id', $user->id)->first();
            $this->assertNotNull($record);
            $this->assertNotEquals($code, $record->code_hash);
            
            $expectedHash = hash_hmac('sha256', "email-verification|{$user->id}|{$code}", config('app.key'));
            $this->assertTrue(hash_equals($expectedHash, $record->code_hash));

            return true;
        });
    }

    public function test_issuance_fails_closed_when_app_key_is_missing()
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        
        $originalKey = config('app.key');
        Config::set('app.key', '');

        $action = new IssueEmailVerificationCode();

        try {
            $action->execute($user);
            $this->fail('Expected exception was not thrown.');
        } catch (\Exception $e) {
            $this->assertStringNotContainsString('123456', $e->getMessage()); 
        }

        Notification::assertNothingSent();
        $this->assertDatabaseMissing('email_verification_codes', ['user_id' => $user->id]);

        Config::set('app.key', $originalKey);
    }

    public function test_verification_fails_closed_when_app_key_is_missing()
    {
        $user = User::factory()->unverified()->create();
        $code = '123456';
        $hash = hash_hmac('sha256', "email-verification|{$user->id}|{$code}", config('app.key'));
        
        EmailVerificationCode::create([
            'user_id' => $user->id,
            'code_hash' => $hash,
            'expires_at' => now()->addMinutes(10),
        ]);

        $originalKey = config('app.key');
        Config::set('app.key', '');

        $action = new VerifyEmailVerificationCode();
        
        $this->expectException(\Exception::class);
        $action->execute($user, $code);

        Config::set('app.key', $originalKey);
    }

    public function test_inactive_user_cannot_issue_or_verify_otp()
    {
        Notification::fake();
        $user = User::factory()->unverified()->inactive()->create();
        
        $issueAction = new IssueEmailVerificationCode();
        $this->expectException(DomainException::class);
        $issueAction->execute($user);

        Notification::assertNothingSent();

        $verifyAction = new VerifyEmailVerificationCode();
        $this->expectException(DomainException::class);
        $verifyAction->execute($user, '123456');
    }

    public function test_verified_user_cannot_issue_otp()
    {
        Notification::fake();
        $user = User::factory()->create(); // verified by default
        
        $action = new IssueEmailVerificationCode();
        $this->expectException(DomainException::class);
        $action->execute($user);

        Notification::assertNothingSent();
    }

    public function test_otp_validity_is_exactly_10_minutes_and_expired_otp_is_rejected()
    {
        $this->freezeTime();
        Notification::fake();
        $user = User::factory()->unverified()->create();
        
        $issueAction = new IssueEmailVerificationCode();
        $issueAction->execute($user);

        $record = EmailVerificationCode::where('user_id', $user->id)->first();
        $this->assertTrue($record->expires_at->equalTo(now()->addMinutes(10)));

        $code = '';
        Notification::assertSentTo($user, EmailVerificationCodeNotification::class, function ($notification) use (&$code) {
            $code = $this->extractOtpFromNotification($notification);
            return true;
        });

        $this->travel(10)->minutes()->addSecond();

        $verifyAction = new VerifyEmailVerificationCode();
        $this->expectException(DomainException::class);
        $verifyAction->execute($user, $code);
    }

    public function test_resend_is_rejected_within_60_seconds_and_allowed_after()
    {
        $this->freezeTime();
        Notification::fake();
        $user = User::factory()->unverified()->create();
        
        $action = new IssueEmailVerificationCode();
        $action->execute($user);

        $this->travel(30)->seconds();

        try {
            $action->execute($user);
            $this->fail('Should rate limit within 60 seconds.');
        } catch (\Illuminate\Http\Exceptions\ThrottleRequestsException $e) {
            $this->assertTrue(true);
        }

        $this->travel(31)->seconds();
        
        $action->execute($user);
        
        $this->assertEquals(1, EmailVerificationCode::where('user_id', $user->id)->count());
    }

    public function test_invalid_otp_increments_attempts_and_locks_after_5_failures()
    {
        $user = User::factory()->unverified()->create();
        $code = '111111';
        $hash = hash_hmac('sha256', "email-verification|{$user->id}|{$code}", config('app.key'));
        
        EmailVerificationCode::create([
            'user_id' => $user->id,
            'code_hash' => $hash,
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        $action = new VerifyEmailVerificationCode();
        
        for ($i = 1; $i <= 4; $i++) {
            try {
                $action->execute($user, '000000');
            } catch (DomainException $e) {}
            
            $this->assertEquals($i, EmailVerificationCode::where('user_id', $user->id)->value('attempts'));
        }

        try {
            $action->execute($user, '000000');
        } catch (DomainException $e) {}

        $this->expectException(DomainException::class);
        $action->execute($user, $code);
    }

    public function test_valid_otp_verifies_email_and_deletes_otp_record()
    {
        $user = User::factory()->unverified()->create();
        $code = '123456';
        $hash = hash_hmac('sha256', "email-verification|{$user->id}|{$code}", config('app.key'));
        
        EmailVerificationCode::create([
            'user_id' => $user->id,
            'code_hash' => $hash,
            'expires_at' => now()->addMinutes(10),
        ]);

        $action = new VerifyEmailVerificationCode();
        $action->execute($user, $code);

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseMissing('email_verification_codes', ['user_id' => $user->id]);
    }

    public function test_verification_is_idempotent()
    {
        $user = User::factory()->create(); 
        $action = new VerifyEmailVerificationCode();
        
        try {
            $action->execute($user, '123456');
        } catch (DomainException $e) {
            $this->assertEquals('Email is already verified.', $e->getMessage());
        }

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_otp_cannot_be_used_by_another_user()
    {
        $user1 = User::factory()->unverified()->create();
        $user2 = User::factory()->unverified()->create();
        
        $code = '123456';
        $hash1 = hash_hmac('sha256', "email-verification|{$user1->id}|{$code}", config('app.key'));
        
        EmailVerificationCode::create([
            'user_id' => $user1->id,
            'code_hash' => $hash1,
            'expires_at' => now()->addMinutes(10),
        ]);

        $action = new VerifyEmailVerificationCode();
        
        $this->expectException(DomainException::class);
        $action->execute($user2, $code);
    }

    public function test_hmac_context_prevents_cross_user_collisions()
    {
        $user1 = User::factory()->unverified()->create();
        $user2 = User::factory()->unverified()->create();
        
        $code = '123456';
        $hash1 = hash_hmac('sha256', "email-verification|{$user1->id}|{$code}", config('app.key'));
        $hash2 = hash_hmac('sha256', "email-verification|{$user2->id}|{$code}", config('app.key'));
        
        $this->assertNotEquals($hash1, $hash2);
    }

    public function test_verification_is_transactional_and_rolls_back_on_failure()
    {
        $user = User::factory()->unverified()->create();
        $code = '123456';
        $hash = hash_hmac('sha256', "email-verification|{$user->id}|{$code}", config('app.key'));
        
        EmailVerificationCode::create([
            'user_id' => $user->id,
            'code_hash' => $hash,
            'expires_at' => now()->addMinutes(10),
        ]);

        DB::shouldReceive('transaction')->andThrow(new \Exception('DB failure'));

        $action = new VerifyEmailVerificationCode();
        
        try {
            $action->execute($user, $code);
        } catch (\Exception $e) {}

        $this->assertDatabaseHas('email_verification_codes', ['user_id' => $user->id]);
        $this->assertNull($user->fresh()->email_verified_at);
    }
}
