<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomResetPasswordNotification extends Notification
{
    use Queueable;

    public $token;

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('KaizenFlow Şifre Sıfırlama')
            ->greeting('Merhaba,')
            ->line('Şifrenizi sıfırlamak için aşağıdaki bağlantıya tıklayın.')
            ->action('Şifremi Sıfırla', $url)
            ->line('Bu bağlantı 60 dakika geçerlidir.')
            ->line('Şifre sıfırlama talebinde bulunmadıysanız herhangi bir işlem yapmanıza gerek yoktur.')
            ->salutation('Saygılarımızla, KaizenFlow');
    }
}
