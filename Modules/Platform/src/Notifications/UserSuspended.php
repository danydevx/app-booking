<?php

namespace Modules\Platform\Notifications;

use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserSuspended extends Notification implements ShouldQueue
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
            ->subject('Tu cuenta de usuario fue suspendida')
            ->greeting('Hola')
            ->line('Tu cuenta de usuario fue suspendida por el equipo de plataforma.')
            ->line("Motivo: {$this->reason}")
            ->line('No podrás iniciar sesión ni acceder a la aplicación mientras dure la suspensión.')
            ->line('Tus datos siguen intactos. Responde a este correo si necesitas revisar la decisión.');
    }
}
