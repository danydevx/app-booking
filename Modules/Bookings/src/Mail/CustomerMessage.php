<?php

namespace Modules\Bookings\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerMessage extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $body,
        public string $unsubscribeUrl,
        public string $customerName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'bookings::mail.customer-message',
            with: [
                'subjectLine' => $this->subjectLine,
                'body' => $this->body,
                'unsubscribeUrl' => $this->unsubscribeUrl,
                'customerName' => $this->customerName,
            ],
        );
    }
}
