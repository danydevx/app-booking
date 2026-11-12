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
            'url' => $this->panelUrl(),
            'kind' => 'inquiry',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $inquiry = $this->inquiry;
        $guests = $inquiry->guestRangeLabel()
            ?? ($inquiry->guests !== null ? (string) $inquiry->guests : 'No indicado');
        $phone = filled($inquiry->phone) ? ' · '.$inquiry->phone : '';

        return (new MailMessage)
            ->from(Branding::fromAddress(), Branding::appName())
            ->replyTo($inquiry->email, $inquiry->name)
            ->subject('Nueva solicitud de reserva')
            ->greeting('Nueva solicitud')
            ->line("{$inquiry->name} quiere rentar {$inquiry->listing->name}.")
            ->line("Fechas: {$inquiry->dateSummary()}.")
            ->when(
                filled($inquiry->event_type),
                fn (MailMessage $mail): MailMessage => $mail
                    ->line("Tipo de evento: {$inquiry->event_type}."),
            )
            ->line("Invitados: {$guests}.")
            ->line("Contacto: {$inquiry->email}{$phone}")
            ->line($inquiry->message ?: 'Sin mensaje adicional.')
            ->action('Ver solicitud', $this->panelUrl());
    }

    private function panelUrl(): string
    {
        return route('bookings.show', [
            'current_team' => $this->inquiry->listing->team->slug,
            'inquiry' => $this->inquiry->id,
        ]);
    }
}
