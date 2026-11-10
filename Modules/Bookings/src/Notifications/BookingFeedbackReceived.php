<?php

namespace Modules\Bookings\Notifications;

use App\Models\User;
use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Bookings\Models\BookingInquiry;

class BookingFeedbackReceived extends Notification implements ShouldQueue
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
            ->subject('Nueva opinión de cliente')
            ->greeting('Opinión recibida')
            ->line("{$this->inquiry->name} dejó una opinión de {$this->inquiry->feedback_rating}/5 para {$this->inquiry->listing->name}.")
            ->line($this->inquiry->feedback_comment ?: 'Sin comentario adicional.')
            ->action('Ver reserva', $this->bookingUrl());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Nueva opinión',
            'message' => "{$this->inquiry->name} dejó {$this->inquiry->feedback_rating}/5 para {$this->inquiry->listing->name}.",
            'url' => $this->bookingUrl(),
            'kind' => 'feedback',
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
