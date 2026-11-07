<?php

namespace Modules\Platform\Notifications;

use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserRestored extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $reason,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->from(Branding::fromAddress(), Branding::appName())
            ->subject('Tu cuenta de usuario fue restaurada')
            ->greeting('Hola')
            ->line('La suspensión de tu cuenta de usuario fue retirada por el equipo de plataforma.')
            ->line("Motivo: {$this->reason}")
            ->line('Ya puedes iniciar sesión y acceder a la aplicación nuevamente.')
            ->line('Gracias por tu paciencia.');
    }
}
