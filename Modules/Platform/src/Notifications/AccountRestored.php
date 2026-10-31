<?php

namespace Modules\Platform\Notifications;

use App\Models\Team;
use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountRestored extends Notification implements ShouldQueue
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
            ->subject('Tu cuenta fue restaurada')
            ->greeting("Hola, {$this->team->name}")
            ->line('La suspensión de la cuenta fue retirada por el equipo de plataforma.')
            ->line("Motivo: {$this->reason}")
            ->line('Tus terrazas publicadas vuelven a mostrarse en el catálogo público.')
            ->line('Gracias por tu paciencia.');
    }
}
