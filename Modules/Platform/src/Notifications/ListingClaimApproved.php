<?php

namespace Modules\Platform\Notifications;

use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Platform\Models\ListingClaim;

class ListingClaimApproved extends Notification implements ShouldQueue
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
            'title' => 'Terraza reclamada',
            'message' => "{$this->claim->listing->name} ya está en tu cuenta.",
            'url' => $this->panelUrl(),
            'kind' => 'claim',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $claim = $this->claim;

        return (new MailMessage)
            ->from(Branding::fromAddress(), Branding::appName())
            ->subject('Tu terraza ya está en tu cuenta')
            ->greeting("Hola {$claim->claimant_name}")
            ->line("Aprobamos la reclamación de {$claim->listing->name}.")
            ->line("La terraza ya pertenece a la cuenta {$claim->targetTeam?->name}.")
            ->line('Ya puedes actualizar fotos, precios, disponibilidad y responder solicitudes desde tu panel.')
            ->action('Administrar mi terraza', $this->panelUrl());
    }

    private function panelUrl(): string
    {
        return route('listings.manage', [
            'current_team' => $this->claim->targetTeam?->slug,
            'listing' => $this->claim->listing->slug,
        ]);
    }
}
