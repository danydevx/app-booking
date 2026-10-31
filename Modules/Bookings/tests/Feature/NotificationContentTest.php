<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Str;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Notifications\NewInquiryOwnerNotification;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class NotificationContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_inquiry_email_contains_listing_and_visitor_data_and_is_queued(): void
    {
        $owner = User::factory()->create();
        $listing = Listing::factory()->forTeam($owner->currentTeam)->published()->create();
        $inquiry = BookingInquiry::factory()->forListing($listing)->create([
            'name' => 'Visitante Ana',
            'email' => 'ana@example.test',
            'phone' => '5555555555',
            'message' => 'Quiero reservar para un cumpleaños.',
        ]);

        $notification = new NewInquiryOwnerNotification($inquiry);

        $this->assertInstanceOf(ShouldQueue::class, $notification);

        $mail = $notification->toMail(new AnonymousNotifiable);
        $body = Str::of(implode(' ', array_filter($mail->introLines, 'is_string')));

        $this->assertSame('Nueva solicitud de reserva', $mail->subject);
        $this->assertTrue($body->contains($listing->name));
        $this->assertTrue($body->contains('ana@example.test'));
        $this->assertTrue($body->contains('Quiero reservar para un cumpleaños.'));
        $this->assertSame('ana@example.test', $mail->replyTo[0][0] ?? null);
    }
}
