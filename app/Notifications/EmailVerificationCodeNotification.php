<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationCodeNotification extends Notification
{
    use Queueable;

    protected string $code;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $code)
    {
        $this->code = $code;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail']; // Database channel is strictly NOT used to prevent leak
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('KaizenFlow E-posta Doğrulama Kodu')
            ->greeting('Merhaba,')
            ->line('E-posta doğrulama kodunuz: **'.$this->code.'**')
            ->line('Bu kod 10 dakika geçerlidir.')
            ->line('Lütfen bu kodu kimseyle paylaşmayın.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            // Do not leak the code in the array representation
        ];
    }
}
