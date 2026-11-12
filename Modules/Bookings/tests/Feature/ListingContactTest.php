<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Mail\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Notifications\InquiryReceivedVisitorNotification;
use Modules\Bookings\Notifications\NewInquiryOwnerNotification;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class ListingContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_listing_exposes_the_contact_modal_configuration(): void
    {
        [, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->published()->create();

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Public/Show')
                ->where('contact.store_url', route('public.listings.contact.store', ['listing' => $listing->slug]))
                ->has('contact.guest_ranges', 4)
                ->where('contact.guest_ranges.0.value', '1-50')
                ->where('contact.default_message', (string) config('bookings.contact_default_message'))
                ->where('contact.privacy_url', null)
                ->where('contact.today', now($listing->timezone)->toDateString()));
    }

    public function test_guest_can_submit_a_contact_request_without_authentication(): void
    {
        Notification::fake();

        [$owner, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->published()->create();
        $month = now($listing->timezone)->addMonth();

        $this->from(route('public.listings.show', ['listing' => $listing->slug]))
            ->post($this->contactUrl($listing), $this->payload([
                'date_mode' => 'approximate',
                'event_month' => $month->format('Y-m'),
                'guest_range' => '51-100',
            ]))
            ->assertRedirect(route('public.listings.show', ['listing' => $listing->slug]));

        $inquiry = BookingInquiry::query()->firstOrFail();

        $this->assertSame($team->id, $inquiry->team_id);
        $this->assertSame($listing->id, $inquiry->listing_id);
        $this->assertNotNull($inquiry->customer_id);
        $this->assertSame(InquiryStatus::New, $inquiry->status);
        $this->assertSame('listing_contact', $inquiry->source);
        $this->assertSame('approximate', $inquiry->date_mode);
        $this->assertSame($month->format('Y-m'), $inquiry->event_month);
        $this->assertSame('51-100', $inquiry->guest_range);
        $this->assertSame('Cumpleaños', $inquiry->event_type);
        $this->assertNull($inquiry->guests);
        $this->assertSame('', $inquiry->phone);
        $this->assertSame($month->copy()->startOfMonth()->toDateString(), $inquiry->start_date->toDateString());
        $this->assertSame($month->copy()->endOfMonth()->toDateString(), $inquiry->end_date->toDateString());
        $this->assertNotNull($inquiry->consent_at);

        $customer = Customer::query()->firstOrFail();
        $this->assertSame($team->id, $customer->team_id);
        $this->assertSame($listing->id, $customer->listing_id);
        $this->assertSame('visitante@example.test', $customer->email);

        Notification::assertSentOnDemand(InquiryReceivedVisitorNotification::class);
        Notification::assertSentTo($owner, NewInquiryOwnerNotification::class);
    }

    public function test_exact_event_date_is_stored_as_a_single_day(): void
    {
        Notification::fake();

        [, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->published()->create();
        $date = now($listing->timezone)->addDays(12)->toDateString();

        $this->post($this->contactUrl($listing), $this->payload([
            'date_mode' => 'exact',
            'event_date' => $date,
        ]))->assertRedirect();

        $inquiry = BookingInquiry::query()->firstOrFail();

        $this->assertSame('exact', $inquiry->date_mode);
        $this->assertNull($inquiry->event_month);
        $this->assertSame($date, $inquiry->start_date->toDateString());
        $this->assertSame($date, $inquiry->end_date->toDateString());
        $this->assertSame(
            CarbonImmutable::parse($date)->format('d/m/Y'),
            $inquiry->dateSummary(),
        );
    }

    public function test_contact_request_validates_required_fields_and_dates(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        [, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->published()->create();
        $route = $this->contactUrl($listing);

        $this->post($route, [])->assertSessionHasErrors([
            'name',
            'email',
            'message',
            'date_mode',
            'guest_range',
        ]);

        $this->post($route, $this->payload([
            'date_mode' => 'exact',
            'event_date' => null,
        ]))->assertSessionHasErrors('event_date');

        $this->post($route, $this->payload([
            'date_mode' => 'approximate',
            'event_month' => null,
        ]))->assertSessionHasErrors('event_month');

        $this->post($route, $this->payload(['email' => 'no-es-correo']))
            ->assertSessionHasErrors('email');

        $this->post($route, $this->payload([
            'date_mode' => 'exact',
            'event_date' => now($listing->timezone)->subDay()->toDateString(),
        ]))->assertSessionHasErrors('event_date');

        $this->post($route, $this->payload([
            'date_mode' => 'approximate',
            'event_month' => now($listing->timezone)->subMonth()->format('Y-m'),
        ]))->assertSessionHasErrors('event_month');

        $this->post($route, $this->payload(['phone' => '12345']))
            ->assertSessionHasErrors('phone');

        $this->post($route, $this->payload(['event_type' => str_repeat('x', 81)]))
            ->assertSessionHasErrors('event_type');

        $this->post($route, $this->payload(['website' => 'https://spam.test']))
            ->assertSessionHasErrors('website');

        $this->assertDatabaseCount('booking_inquiries', 0);
    }

    public function test_owner_and_tenant_are_resolved_from_the_listing_not_the_request(): void
    {
        Notification::fake();

        [, $ownerTeam] = $this->ownerWithTeam();
        [, $otherTeam] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($ownerTeam)->published()->create();
        $otherListing = Listing::factory()->forTeam($otherTeam)->published()->create();

        $this->post($this->contactUrl($listing), $this->payload([
            'team_id' => $otherTeam->id,
            'listing_id' => $otherListing->id,
            'owner_id' => 999999,
        ]))->assertRedirect();

        $inquiry = BookingInquiry::query()->firstOrFail();

        $this->assertSame($ownerTeam->id, $inquiry->team_id);
        $this->assertSame($listing->id, $inquiry->listing_id);
        $this->assertSame($listing->id, $inquiry->customer?->listing_id);
    }

    public function test_contact_requests_are_rejected_for_unpublished_or_out_of_coverage_listings(): void
    {
        [, $team] = $this->ownerWithTeam();

        $draft = Listing::factory()->forTeam($team)->create();
        $suspended = Listing::factory()->forTeam($team)->suspended()->create();
        $outOfCoverage = Listing::factory()->forTeam($team)->published()->create([
            'state' => 'CDMX',
            'city' => 'Cuauhtémoc',
        ]);

        foreach ([$draft, $suspended, $outOfCoverage] as $listing) {
            $this->post($this->contactUrl($listing), $this->payload())
                ->assertNotFound();
        }

        $this->assertDatabaseCount('booking_inquiries', 0);
    }

    public function test_duplicate_submissions_in_the_dedupe_window_create_one_inquiry(): void
    {
        Notification::fake();

        [$owner, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->published()->create();
        $payload = $this->payload();

        $this->post($this->contactUrl($listing), $payload)->assertRedirect();
        $this->post($this->contactUrl($listing), $payload)->assertRedirect();

        $this->assertDatabaseCount('booking_inquiries', 1);
        Notification::assertSentToTimes($owner, NewInquiryOwnerNotification::class, 1);
    }

    public function test_contact_route_is_rate_limited(): void
    {
        Notification::fake();

        [, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->published()->create();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post($this->contactUrl($listing), $this->payload([
                'email' => "visitor{$attempt}@example.test",
            ]))->assertRedirect();
        }

        $this->post($this->contactUrl($listing), $this->payload([
            'email' => 'blocked@example.test',
        ]))->assertTooManyRequests();
    }

    public function test_contact_request_survives_a_mail_transport_failure(): void
    {
        $this->mock(Factory::class, function ($mock): void {
            $mock->shouldReceive('mailer')->andThrow(new \RuntimeException('mail server unavailable'));
        });

        [, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->published()->create();

        $this->post($this->contactUrl($listing), $this->payload())
            ->assertRedirect(route('public.listings.show', ['listing' => $listing->slug]));

        $this->assertDatabaseCount('booking_inquiries', 1);
        $this->assertDatabaseHas('booking_inquiries', [
            'listing_id' => $listing->id,
            'email' => 'visitante@example.test',
        ]);
    }

    /**
     * @return array{0: User, 1: Team}
     */
    private function ownerWithTeam(): array
    {
        $owner = User::factory()->create();

        return [$owner, $owner->currentTeam];
    }

    private function contactUrl(Listing $listing): string
    {
        return route('public.listings.contact.store', ['listing' => $listing->slug]);
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
            'phone' => null,
            'message' => 'Hola, me gustaría recibir más información sobre esta terraza.',
            'event_type' => 'Cumpleaños',
            'date_mode' => 'exact',
            'event_date' => now()->addDays(10)->toDateString(),
            'guest_range' => '1-50',
            'website' => '',
        ], $overrides);
    }
}
