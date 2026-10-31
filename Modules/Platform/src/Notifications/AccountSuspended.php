<?php

namespace Modules\Platform\Notifications;

use App\Models\Team;
use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountSuspended extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Team $team,
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
            ->subject('Tu cuenta fue suspendida temporalmente')
            ->greeting("Hola, {$this->team->name}")
            ->line('La cuenta fue suspendida por el equipo de plataforma.')
            ->line("Motivo: {$this->reason}")
            ->line('Las terrazas de la cuenta dejaron de mostrarse en el catálogo público mientras dure la suspensión.')
            ->line('Tus datos siguen intactos. Responde a este correo si necesitas revisar la decisión.');
    }
}
