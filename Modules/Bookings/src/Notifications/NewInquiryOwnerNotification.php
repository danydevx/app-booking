<?php

namespace Modules\Bookings\Notifications;

use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Bookings\Models\BookingInquiry;

class NewInquiryOwnerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BookingInquiry $inquiry) {}

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
        $inquiry = $this->inquiry;

        return [
            'title' => 'Nueva solicitud',
            'message' => "{$inquiry->name} quiere rentar {$inquiry->listing->name}.",
            'url' => route('bookings.show', [
                'current_team' => $inquiry->listing->team->slug,
                'inquiry' => $inquiry->id,
            ]),
            'kind' => 'inquiry',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $inquiry = $this->inquiry;

        return (new MailMessage)
            ->from(Branding::fromAddress(), Branding::appName())
            ->replyTo($inquiry->email, $inquiry->name)
            ->subject('Nueva solicitud de reserva')
            ->greeting('Nueva solicitud')
            ->line("{$inquiry->name} quiere rentar {$inquiry->listing->name}.")
            ->line("Fechas: {$inquiry->start_date->format('d/m/Y')} al {$inquiry->end_date->format('d/m/Y')}.")
            ->line("Contacto: {$inquiry->email} · {$inquiry->phone}")
            ->line($inquiry->message ?: 'Sin mensaje adicional.');
    }
}
