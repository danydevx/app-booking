<?php

namespace Modules\Bookings\Notifications;

use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Bookings\Models\CustomerQuote;

class CustomerQuoteAccepted extends Notification implements ShouldQueue
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
            ->subject('Cotización aceptada')
            ->greeting('Hola')
            ->line("{$this->quote->customer->name} aceptó la cotización de {$this->quote->listing->name}.")
            ->line("Fechas: {$this->quote->start_date->format('d/m/Y')} al {$this->quote->end_date->format('d/m/Y')}")
            ->line('Total: $'.number_format($this->quote->subtotal_cents / 100, 2)." {$this->quote->currency}")
            ->action('Revisar cotización', route('bookings.quotes.show', [
                'current_team' => $this->quote->team->slug,
                'quote' => $this->quote->id,
            ]))
            ->line('Puedes convertirla en reserva desde la ficha de la cotización.');
    }
}
