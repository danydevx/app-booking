<?php

namespace Modules\Listings\Notifications;

use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Listings\Models\Listing;

class ListingReturnedToDirectory extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Listing $listing,
        public string $reason,
    ) {}

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
            'title' => 'Terraza devuelta al Directorio',
            'message' => "{$this->listing->name} volvió al Directorio de la plataforma.",
            'url' => route('public.listings.show', ['listing' => $this->listing->slug]),
            'kind' => 'claim',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->from(Branding::fromAddress(), Branding::appName())
            ->subject('Tu terraza volvió al Directorio')
            ->greeting('Hola')
            ->line("La terraza {$this->listing->name} dejó de estar vinculada a tu cuenta y volvió al Directorio de la plataforma.")
            ->line("Motivo: {$this->reason}")
            ->line('Si crees que se trata de un error, responde a este correo para revisarlo.')
            ->action('Ver la terraza', route('public.listings.show', ['listing' => $this->listing->slug]));
    }
}
