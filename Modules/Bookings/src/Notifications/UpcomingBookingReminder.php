<?php

namespace Modules\Bookings\Notifications;

use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Bookings\Models\BookingInquiry;

class UpcomingBookingReminder extends Notification implements ShouldQueue
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
        return ['mail', 'database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Reserva próxima',
            'message' => "La reserva de {$this->inquiry->listing->name} comienza en {$this->inquiry->start_date->diffForHumans()}.",
            'url' => route('bookings.show', [
                'current_team' => $this->inquiry->listing->team->slug,
                'inquiry' => $this->inquiry->id,
            ]),
            'kind' => 'booking',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->from(Branding::fromAddress(), Branding::appName())
            ->subject('Tu reserva comienza pronto')
            ->greeting('Hola')
            ->line('Tienes una reserva aceptada próxima a comenzar.')
            ->line("Terraza: {$this->inquiry->listing->name}")
            ->line("Fechas: {$this->inquiry->start_date->format('d/m/Y')} al {$this->inquiry->end_date->format('d/m/Y')}")
            ->line("Cliente: {$this->inquiry->name} ({$this->inquiry->email})")
            ->line('Revisa los detalles y confirma los últimos puntos con el cliente.');
    }
}
