<?php

namespace Modules\Bookings\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Bookings\Models\BookingInquiry;

class InquiryReceivedVisitorNotification extends Notification implements ShouldQueue
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
            ->subject('Recibimos tu solicitud de reserva')
            ->greeting("Hola {$inquiry->name}")
            ->line("Recibimos tu interés por {$inquiry->listing->name}.")
            ->line("Fechas solicitadas: {$inquiry->start_date->format('d/m/Y')} al {$inquiry->end_date->format('d/m/Y')}.")
            ->line('El anfitrión revisará la disponibilidad y se pondrá en contacto contigo.');
    }
}
