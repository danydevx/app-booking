<?php

namespace Modules\Bookings\Notifications;

use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Bookings\Models\BookingInquiry;

class InquiryDeclinedVisitorNotification extends Notification implements ShouldQueue
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
            ->subject('Actualización de tu solicitud de reserva')
            ->greeting("Hola {$inquiry->name}")
            ->line("El anfitrión no pudo aceptar tu solicitud para {$inquiry->listing->name}.")
            ->line('Puedes buscar otra terraza o intentar con otras fechas.');
    }
}
