<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TemporaryUserPasswordNotification extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $temporaryPassword) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tus credenciales de acceso')
            ->markdown('mail.users.temporary-password', [
                'name' => $notifiable->name,
                'email' => $notifiable->email,
                'temporaryPassword' => $this->temporaryPassword,
                'loginUrl' => route('login'),
            ]);
    }
}
