<?php

namespace Modules\Bookings\Notifications;

use App\Models\User;
use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Bookings\Models\BookingInquiry;

class BookingConfirmedByCustomer extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public BookingInquiry $inquiry,
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
        return (new MailMessage)
            ->from(Branding::fromAddress(), Branding::appName())
            ->subject('El cliente confirmó su reserva')
            ->greeting('Reserva confirmada')
            ->line("{$this->inquiry->name} confirmó los datos de su reserva en {$this->inquiry->listing->name}.")
            ->line("Fechas: {$this->inquiry->start_date->format('d/m/Y')} al {$this->inquiry->end_date->format('d/m/Y')}.")
            ->line("Invitados: {$this->inquiry->guests}.")
            ->action('Ver reserva', $this->bookingUrl());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Reserva confirmada',
            'message' => "{$this->inquiry->name} confirmó su reserva en {$this->inquiry->listing->name}.",
            'url' => $this->bookingUrl(),
            'kind' => 'booking',
        ];
    }

    private function bookingUrl(): string
    {
        return route('bookings.show', [
            'current_team' => $this->inquiry->listing->team->slug,
            'inquiry' => $this->inquiry->id,
        ]);
    }
}
