<?php

namespace App\Notifications;

use App\Enums\KaizenNotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KaizenBusinessNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly KaizenNotificationType $type,
        public readonly int $kaizenId,
        public readonly string $kaizenCode,
        public readonly string $kaizenTitle,
        public readonly ?string $targetDate = null
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->getSubject())
            ->greeting('Merhaba,')
            ->line($this->getEventDescription())
            ->line('Kaizen Kodu: '.$this->kaizenCode)
            ->line('Kaizen Başlığı: '.$this->kaizenTitle);

        if ($this->type === KaizenNotificationType::IMPLEMENTATION_ASSIGNED && $this->targetDate) {
            $message->line('Hedef Tarih: '.$this->targetDate);
        }

        $message->action('Kaizen\'i Görüntüle', route('kaizens.show', $this->kaizenId));

        return $message;
    }

    private function getSubject(): string
    {
        return match ($this->type) {
            KaizenNotificationType::SUBMITTED_FOR_REVIEW => 'Yeni Kaizen inceleme bekliyor',
            KaizenNotificationType::APPROVAL_STAGE_READY => 'Kaizen onay sıranıza geldi',
            KaizenNotificationType::REVISION_REQUESTED => 'Kaizen için revizyon istendi',
            KaizenNotificationType::REJECTED => 'Kaizen reddedildi',
            KaizenNotificationType::APPROVED => 'Kaizen onaylandı',
            KaizenNotificationType::IMPLEMENTATION_ASSIGNED => 'Kaizen uygulaması size atandı',
            KaizenNotificationType::IMPLEMENTATION_STARTED => 'Kaizen uygulaması başlatıldı',
            KaizenNotificationType::IMPLEMENTATION_COMPLETED => 'Kaizen uygulaması tamamlandı',
        };
    }

    private function getEventDescription(): string
    {
        return match ($this->type) {
            KaizenNotificationType::SUBMITTED_FOR_REVIEW => 'Yeni bir Kaizen inceleme için gönderildi.',
            KaizenNotificationType::APPROVAL_STAGE_READY => 'Bir Kaizen onayınız için bekliyor.',
            KaizenNotificationType::REVISION_REQUESTED => 'Gönderdiğiniz Kaizen için revizyon talep edildi.',
            KaizenNotificationType::REJECTED => 'Gönderdiğiniz Kaizen reddedildi.',
            KaizenNotificationType::APPROVED => 'Gönderdiğiniz Kaizen onaylandı.',
            KaizenNotificationType::IMPLEMENTATION_ASSIGNED => 'Bir Kaizen\'in uygulaması tarafınıza atandı.',
            KaizenNotificationType::IMPLEMENTATION_STARTED => 'Kaizeninizin uygulaması başlatıldı.',
            KaizenNotificationType::IMPLEMENTATION_COMPLETED => 'Kaizeninizin uygulaması tamamlandı.',
        };
    }
}
