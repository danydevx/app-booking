<?php

namespace Modules\Bookings\Notifications;

use App\Models\User;
use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Bookings\Models\BookingContract;

class BookingContractAccepted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public BookingContract $contract,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if ($notifiable instanceof User && ! $notifiable->wantsInquiryEmails()) {
            return ['database'];
        }

        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->contract->bookingInquiry;

        return (new MailMessage)
            ->from(Branding::fromAddress(), Branding::appName())
            ->subject('Contrato aceptado por el cliente')
            ->greeting('Contrato aceptado')
            ->line("{$this->contract->accepted_by_name} aceptó el contrato de la reserva en {$booking->listing->name}.")
            ->line("Fechas: {$booking->start_date->format('d/m/Y')} al {$booking->end_date->format('d/m/Y')}.")
            ->action('Ver reserva', $this->bookingUrl());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Contrato aceptado',
            'message' => "{$this->contract->accepted_by_name} aceptó el contrato de {$this->contract->bookingInquiry->listing->name}.",
            'url' => $this->bookingUrl(),
            'kind' => 'contract',
        ];
    }

    private function bookingUrl(): string
    {
        $booking = $this->contract->bookingInquiry;

        return route('bookings.show', [
            'current_team' => $booking->listing->team->slug,
            'inquiry' => $booking->id,
        ]);
    }
}
