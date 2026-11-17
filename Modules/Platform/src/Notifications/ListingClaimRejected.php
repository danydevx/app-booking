<?php

namespace Modules\Platform\Notifications;

use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Platform\Models\ListingClaim;

class ListingClaimRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ListingClaim $claim) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Reclamación no aprobada',
            'message' => "No pudimos aprobar la reclamación de {$this->claim->listing->name}.",
            'url' => route('public.listings.show', ['listing' => $this->claim->listing->slug]),
            'kind' => 'claim',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $claim = $this->claim;

        return (new MailMessage)
            ->from(Branding::fromAddress(), Branding::appName())
            ->subject('Reclamación de terraza no aprobada')
            ->greeting("Hola {$claim->claimant_name}")
            ->line("Revisamos la reclamación de {$claim->listing->name} y no pudimos aprobarla.")
            ->when(
                filled($claim->rejection_reason),
                fn (MailMessage $mail): MailMessage => $mail
                    ->line("Motivo: {$claim->rejection_reason}"),
            )
            ->line('Si cuentas con documentación adicional, responde a este correo o envía una nueva reclamación con más información.')
            ->action('Ver la terraza', route('public.listings.show', ['listing' => $claim->listing->slug]));
    }
}
