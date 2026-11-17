<?php

namespace Modules\Bookings\Notifications;

use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Bookings\Models\BookingInquiry;

class NewInquiryDirectoryNotification extends Notification implements ShouldQueue
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
        $phone = filled($inquiry->phone) ? ' · '.$inquiry->phone : '';
        $guests = $inquiry->guestRangeLabel()
            ?? ($inquiry->guests !== null ? (string) $inquiry->guests : 'No indicado');

        return (new MailMessage)
            ->from(Branding::fromAddress(), Branding::appName())
            ->replyTo($inquiry->email, $inquiry->name)
            ->subject('Solicitud para una terraza sin reclamar')
            ->greeting('Nueva solicitud en el Directorio')
            ->line("{$inquiry->name} está interesado en {$inquiry->listing->name}.")
            ->line('La terraza todavía no tiene propietario reclamado. Revisa la solicitud y contacta al anunciante o al propietario cuando reclame el anuncio.')
            ->line("Fechas: {$inquiry->dateSummary()}.")
            ->when(
                filled($inquiry->event_type),
                fn (MailMessage $mail): MailMessage => $mail
                    ->line("Tipo de evento: {$inquiry->event_type}."),
            )
            ->line("Invitados: {$guests}.")
            ->line("Contacto: {$inquiry->email}{$phone}")
            ->line($inquiry->message ?: 'Sin mensaje adicional.')
            ->action(
                'Ver la terraza',
                route('public.listings.show', ['listing' => $inquiry->listing->slug]),
            );
    }
}
