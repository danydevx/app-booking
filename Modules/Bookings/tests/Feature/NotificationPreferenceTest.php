<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Notifications\InquiryReceivedVisitorNotification;
use Modules\Bookings\Notifications\NewInquiryOwnerNotification;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class NotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_disable_inquiry_emails_without_affecting_the_visitor(): void
    {
        Notification::fake();

        $owner = User::factory()->create([
            'notification_preferences' => ['inquiry_emails' => false],
        ]);
        $team = $owner->currentTeam;
        $listing = Listing::factory()->forTeam($team)->published()->create();

        $this->post(
            route('public.bookings.inquiries.store', ['listing' => $listing->slug]),
            $this->payload(),
        )->assertRedirect();

        Notification::assertNotSentTo($owner, NewInquiryOwnerNotification::class);
        Notification::assertSentOnDemand(InquiryReceivedVisitorNotification::class);

        $this->assertSame(
            1,
            BookingInquiry::query()->where('team_id', $team->id)->count(),
        );
    }

    public function test_owner_receives_inquiry_emails_by_default(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $listing = Listing::factory()->forTeam($owner->currentTeam)->published()->create();

        $this->post(
            route('public.bookings.inquiries.store', ['listing' => $listing->slug]),
            $this->payload(),
        )->assertRedirect();

        Notification::assertSentTo($owner, NewInquiryOwnerNotification::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'name' => 'Visitante Prueba',
            'email' => 'visitante@example.test',
            'phone' => '5555555555',
            'whatsapp_phone' => '5215555555555',
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(12)->toDateString(),
            'event_type' => 'Cumpleaños',
            'guests' => 40,
            'message' => 'Quiero celebrar un cumpleaños.',
            'consent' => true,
            'website' => '',
        ];
    }
}
