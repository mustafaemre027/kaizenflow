<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitationNotification extends Notification
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

        $expireMinutes = config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('KaizenFlow Hesap Kurulumu')
            ->greeting('Merhaba,')
            ->line('Sizin için bir KaizenFlow hesabı oluşturuldu.')
            ->line('Hesabınızı kullanmaya başlamak için lütfen aşağıdaki bağlantıya tıklayarak kendi güvenli parolanızı belirleyin.')
            ->action('Parolamı Belirle', $url)
            ->line("Bu bağlantı {$expireMinutes} dakika boyunca geçerlidir ve güvenlik amacıyla başkalarıyla paylaşılmamalıdır.")
            ->line('Eğer bu daveti beklemiyorsanız, lütfen sistem yöneticinizle iletişime geçin.')
            ->salutation('Saygılarımızla, KaizenFlow');
    }
}
