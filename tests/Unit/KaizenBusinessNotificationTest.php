<?php

namespace Tests\Unit;

use App\Enums\KaizenNotificationType;
use App\Notifications\KaizenBusinessNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class KaizenBusinessNotificationTest extends TestCase
{
    public function test_it_creates_notification_with_after_commit()
    {
        $notification = new KaizenBusinessNotification(
            KaizenNotificationType::SUBMITTED_FOR_REVIEW,
            1,
            'KZN-001',
            'Test Kaizen',
            null
        );

        $this->assertEquals('mail', $notification->via(new \stdClass)[0]);
    }

    public function test_it_formats_mail_message_safely()
    {
        $notification = new KaizenBusinessNotification(
            KaizenNotificationType::IMPLEMENTATION_ASSIGNED,
            5,
            'KZN-005',
            '<script>alert("xss")</script>',
            '2024-12-31'
        );

        $mail = $notification->toMail(new \stdClass);
        $this->assertInstanceOf(MailMessage::class, $mail);

        $this->assertEquals('Kaizen uygulaması size atandı', $mail->subject);
        $this->assertStringContainsString('KZN-005', $mail->introLines[1]);
        $this->assertStringContainsString('<script>', $mail->introLines[2]);

        $this->assertEquals(route('kaizens.show', 5), $mail->actionUrl);
        $this->assertEquals('Kaizen\'i Görüntüle', $mail->actionText);
        $this->assertEquals('Hedef Tarih: 2024-12-31', $mail->introLines[3]);
    }
}
