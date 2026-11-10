<?php

namespace Modules\Bookings\Notifications;

use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Bookings\Models\BookingInquiry;

class ManualBookingConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BookingInquiry $inquiry) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $inquiry = $this->inquiry;

        return (new MailMessage)
            ->from(Branding::fromAddress(), Branding::appName())
            ->subject('Tu reserva quedó confirmada')
            ->greeting("Hola {$inquiry->name}")
            ->line("El anfitrión registró tu reserva para {$inquiry->listing->name}.")
            ->line("Fechas confirmadas: {$inquiry->start_date->format('d/m/Y')} al {$inquiry->end_date->format('d/m/Y')}.")
            ->line('El anticipo y los detalles finales se acuerdan directamente con el anfitrión.');
    }
}
