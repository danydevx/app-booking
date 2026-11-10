<?php

namespace Modules\Bookings\Notifications;

use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Bookings\Models\CustomerQuote;

class CustomerQuoteSent extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CustomerQuote $quote,
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
            ->subject("Cotización · {$this->quote->listing->name}")
            ->greeting("Hola {$this->quote->customer->name}")
            ->line("Te compartimos la cotización para {$this->quote->listing->name}.")
            ->line("Fechas: {$this->quote->start_date->format('d/m/Y')} al {$this->quote->end_date->format('d/m/Y')}")
            ->line('Total: $'.number_format($this->quote->subtotal_cents / 100, 2)." {$this->quote->currency}")
            ->line('Anticipo: $'.number_format($this->quote->deposit_cents / 100, 2)." {$this->quote->currency}")
            ->action('Ver cotización', $this->quote->publicUrl())
            ->line('Puedes aceptar o rechazar la cotización desde el enlace.');
    }
}
