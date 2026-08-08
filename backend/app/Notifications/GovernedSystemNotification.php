<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GovernedSystemNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $eventType, public readonly string $title, public readonly string $message, public readonly ?string $route, public readonly array $context = []) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return ['event_type' => $this->eventType, 'title' => $this->title, 'message' => $this->message, 'route' => $this->route, 'context' => $this->context];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject($this->title)->line($this->message)->when($this->route, fn (MailMessage $mail) => $mail->action('Buka SIMUTU', url($this->route)));
    }
}
