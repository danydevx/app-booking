<?php

namespace Modules\Bookings\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Bookings\Models\BookingInquiry;

class InquiryAcceptedVisitorNotification extends Notification implements ShouldQueue
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
            ->subject('Tu solicitud de reserva fue aceptada')
            ->greeting("Hola {$inquiry->name}")
            ->line("El anfitrión aceptó tu solicitud para {$inquiry->listing->name}.")
            ->line("Fechas reservadas: {$inquiry->start_date->format('d/m/Y')} al {$inquiry->end_date->format('d/m/Y')}.")
            ->line('El anticipo y los detalles finales se acuerdan directamente con el anfitrión.');
    }
}
