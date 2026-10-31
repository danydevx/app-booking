<?php

namespace Modules\Billing\Notifications;

use App\Models\Team;
use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Team $team) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $graceDays = (int) config('billing.past_due_grace_days', 3);
        $message = (new MailMessage)
            ->from(Branding::fromAddress(), Branding::appName())
            ->subject('No pudimos procesar el pago de tu suscripción')
            ->greeting("Hola, {$this->team->name}")
            ->line('Stripe reportó un pago pendiente para la suscripción de la cuenta.')
            ->line("Mantendremos el plan de pago durante {$graceDays} días de gracia mientras actualizas tu método de pago.")
            ->line('Después de ese periodo la cuenta vuelve al plan Gratis sin borrar tus datos.')
            ->action('Revisar facturación', route('billing.index', ['current_team' => $this->team->slug]));

        if (Branding::supportEmail()) {
            $message->line('Si necesitas ayuda, escribe a '.Branding::supportEmail().'.');
        }

        return $message;
    }
}
