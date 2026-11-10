<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class LeadAttributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_inquiry_stores_utm_data_from_hidden_fields(): void
    {
        Notification::fake();

        [, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->published()->create();

        $this->post(
            route('public.bookings.inquiries.store', ['listing' => $listing->slug]),
            $this->payload([
                'source_detail' => 'instagram',
                'utm_source' => 'instagram',
                'utm_medium' => 'social',
                'utm_campaign' => 'verano-2026',
                'referrer' => 'https://instagram.com/p/abc',
            ]),
        )->assertRedirect();

        $inquiry = BookingInquiry::query()->firstOrFail();

        $this->assertSame('instagram', $inquiry->source_detail);
        $this->assertSame('instagram', $inquiry->utm_source);
        $this->assertSame('social', $inquiry->utm_medium);
        $this->assertSame('verano-2026', $inquiry->utm_campaign);
        $this->assertSame('https://instagram.com/p/abc', $inquiry->referrer);
    }

    public function test_availability_page_keeps_attribution_for_the_submission(): void
    {
        Notification::fake();

        [, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->published()->create();

        $this->get(route('public.bookings.availability', [
            'listing' => $listing->slug,
            'utm_source' => 'facebook',
            'utm_campaign' => 'jardines',
        ]))->assertOk();

        $this->post(
            route('public.bookings.inquiries.store', ['listing' => $listing->slug]),
            $this->payload(),
        )->assertRedirect();

        $inquiry = BookingInquiry::query()->firstOrFail();

        $this->assertSame('facebook', $inquiry->utm_source);
        $this->assertSame('jardines', $inquiry->utm_campaign);
        $this->assertSame('facebook', $inquiry->source_detail);
    }

    public function test_external_referrer_is_captured_on_the_public_listing(): void
    {
        Notification::fake();

        [, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->published()->create();

        $this->withHeader('referer', 'https://google.com/search?q=terraza')
            ->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertOk();

        $this->post(
            route('public.bookings.inquiries.store', ['listing' => $listing->slug]),
            $this->payload(),
        )->assertRedirect();

        $inquiry = BookingInquiry::query()->firstOrFail();

        $this->assertSame('google.com', $inquiry->source_detail);
        $this->assertSame('https://google.com/search?q=terraza', $inquiry->referrer);
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
            'name' => 'Visitante Atribución',
            'email' => 'atribucion@example.test',
            'phone' => '5555555555',
            'whatsapp_phone' => null,
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(12)->toDateString(),
            'event_type' => 'Cumpleaños',
            'guests' => 40,
            'message' => 'Quiero reservar.',
            'consent' => true,
            'website' => '',
        ], $overrides);
    }
}
