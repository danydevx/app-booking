<?php

namespace App\Notifications;

use App\Models\User;
use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->from(Branding::fromAddress(), Branding::appName())
            ->subject('Bienvenido a '.Branding::appName())
            ->greeting("Hola {$this->user->name}")
            ->line('Tu cuenta está lista para publicar terrazas y recibir solicitudes.')
            ->line('Completa la marca y los datos de contacto de tu cuenta para que los visitantes sepan cómo localizarte.')
            ->action('Ir a mi panel', url('/'));

        if (Branding::supportEmail()) {
            $message->line('Si necesitas ayuda, escribe a '.Branding::supportEmail().'.');
        }

        foreach (Branding::legalLinks() as $link) {
            $message->line($link);
        }

        return $message;
    }
}
