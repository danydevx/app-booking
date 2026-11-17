<?php

namespace Modules\Platform\Notifications;

use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Platform\Models\ListingClaim;

class ListingClaimSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ListingClaim $claim) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof AnonymousNotifiable
            ? ['mail']
            : ['mail', 'database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Nueva reclamación de terraza',
            'message' => "{$this->claim->claimant_name} quiere reclamar {$this->claim->listing->name}.",
            'url' => route('platform.claims.show', ['claim' => $this->claim->id]),
            'kind' => 'claim',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $claim = $this->claim;

        return (new MailMessage)
            ->from(Branding::fromAddress(), Branding::appName())
            ->subject('Nueva reclamación de terraza')
            ->greeting('Nueva reclamación')
            ->line("{$claim->claimant_name} quiere reclamar {$claim->listing->name}.")
            ->line("Cuenta solicitada: {$claim->business_name}.")
            ->line("Rol declarado: {$claim->role->label()}.")
            ->line("Correo: {$claim->claimant_email}")
            ->line("Teléfono: {$claim->claimant_phone}")
            ->line('Verificación declarada:')
            ->line($claim->message)
            ->action('Revisar reclamación', route('platform.claims.show', ['claim' => $claim->id]));
    }
}
