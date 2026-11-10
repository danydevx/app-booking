<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Notifications\InquiryReceivedVisitorNotification;
use Modules\Bookings\Notifications\NewInquiryOwnerNotification;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class InquirySubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_inquiry_is_persisted_with_a_customer_and_notifications(): void
    {
        Notification::fake();

        [$owner, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->published()->create();

        $response = $this->post(
            route('public.bookings.inquiries.store', ['listing' => $listing->slug]),
            $this->payload(),
        );

        $response->assertRedirect(route('public.bookings.availability', ['listing' => $listing->slug]));
        $response->assertSessionHas('inquiry_submitted');

        $inquiry = BookingInquiry::query()->firstOrFail();

        $this->assertSame($team->id, $inquiry->team_id);
        $this->assertSame($listing->id, $inquiry->listing_id);
        $this->assertSame(InquiryStatus::New, $inquiry->status);
        $this->assertNotNull($inquiry->consent_at);
        $this->assertNotNull($inquiry->ip_hash);
        $this->assertNotNull($inquiry->customer_id);

        $customer = Customer::query()->firstOrFail();
        $this->assertSame($team->id, $customer->team_id);
        $this->assertSame('visitante@example.test', $customer->email);

        Notification::assertSentOnDemand(InquiryReceivedVisitorNotification::class);
        Notification::assertSentTo($owner, NewInquiryOwnerNotification::class);
        $this->assertNotNull($owner->fresh());
    }

    public function test_inquiries_are_not_accepted_for_draft_or_suspended_listings(): void
    {
        $team = Team::factory()->create();
        $draft = Listing::factory()->forTeam($team)->create();
        $suspended = Listing::factory()->forTeam($team)->suspended()->create();

        $this->post(route('public.bookings.inquiries.store', ['listing' => $draft->slug]), $this->payload())
            ->assertNotFound();
        $this->post(route('public.bookings.inquiries.store', ['listing' => $suspended->slug]), $this->payload())
            ->assertNotFound();

        $this->assertDatabaseCount('booking_inquiries', 0);
    }

    public function test_inquiry_validates_dates_honeypot_and_message_size(): void
    {
        $team = Team::factory()->create();
        $listing = Listing::factory()->forTeam($team)->published()->create();
        $route = route('public.bookings.inquiries.store', ['listing' => $listing->slug]);
        $timezone = $listing->timezone;

        $this->post($route, $this->payload([
            'start_date' => now($timezone)->subDay()->toDateString(),
            'end_date' => now($timezone)->toDateString(),
        ]))->assertSessionHasErrors('start_date');

        $this->post($route, $this->payload([
            'start_date' => now($timezone)->addDays(5)->toDateString(),
            'end_date' => now($timezone)->addDays(1)->toDateString(),
        ]))->assertSessionHasErrors('end_date');

        $this->post($route, $this->payload([
            'start_date' => now($timezone)->addDay()->toDateString(),
            'end_date' => now($timezone)->addDays(60)->toDateString(),
        ]))->assertSessionHasErrors('end_date');

        $this->post($route, $this->payload(['website' => 'https://spam.test']))
            ->assertSessionHasErrors('website');

        $this->post($route, $this->payload(['message' => str_repeat('x', 2001)]))
            ->assertSessionHasErrors('message');

        $this->assertDatabaseCount('booking_inquiries', 0);
    }

    public function test_public_inquiry_route_is_rate_limited(): void
    {
        Notification::fake();

        $team = Team::factory()->create();
        $listing = Listing::factory()->forTeam($team)->published()->create();
        $route = route('public.bookings.inquiries.store', ['listing' => $listing->slug]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post($route, $this->payload([
                'email' => "visitor{$attempt}@example.test",
            ]))->assertRedirect();
        }

        $this->post($route, $this->payload(['email' => 'blocked@example.test']))
            ->assertTooManyRequests();
    }

    public function test_notifications_are_queued_not_sent_synchronously(): void
    {
        Queue::fake();

        [, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->published()->create();

        $this->post(
            route('public.bookings.inquiries.store', ['listing' => $listing->slug]),
            $this->payload(),
        )->assertRedirect();

        Queue::assertPushed(SendQueuedNotifications::class, 3);
    }

    /**
     * @return array{0: User, 1: Team}
     */
    private function ownerWithTeam(): array
    {
        $owner = User::factory()->create();

        return [$owner, $owner->currentTeam];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
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
        ], $overrides);
    }
}
