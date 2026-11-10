<?php

namespace Modules\Bookings\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Bookings\Actions\CreateBookingInquiry;
use Modules\Bookings\Http\Requests\StoreBookingInquiryRequest;
use Modules\Bookings\Support\AvailabilityService;
use Modules\Bookings\Support\LeadAttribution;
use Modules\Listings\Models\Listing;
use Modules\Listings\Support\ListingPresenter;

class PublicAvailabilityController extends Controller
{
    public function show(
        Request $request,
        Listing $listing,
        AvailabilityService $availability,
    ): Response {
        abort_unless($listing->isPubliclyVisible(), 404);

        $listing->load('media');

        LeadAttribution::capture($request, $listing);

        $today = CarbonImmutable::now($listing->timezone)->toDateString();
        $end = CarbonImmutable::now($listing->timezone)
            ->addDays((int) config('bookings.public_calendar_days', 90))
            ->toDateString();

        return Inertia::render('Bookings/Public/Availability', [
            'listing' => ListingPresenter::publicDetail($listing),
            'blocked_dates' => $availability->blockedDates($listing, $today, $end),
            'attribution' => LeadAttribution::resolve($request, $listing),
            'submitted' => $request->session()->get('inquiry_submitted'),
            'urls' => [
                'index' => route('public.listings.index'),
                'listing' => route('public.listings.show', ['listing' => $listing->slug]),
                'store' => route('public.bookings.inquiries.store', ['listing' => $listing->slug]),
            ],
        ]);
    }

    public function store(
        StoreBookingInquiryRequest $request,
        Listing $listing,
        CreateBookingInquiry $createInquiry,
    ): RedirectResponse {
        abort_unless($listing->isPubliclyVisible(), 404);

        $data = $request->validated();

        $attribution = array_filter([
            'source_detail' => $data['source_detail'] ?? null,
            'utm_source' => $data['utm_source'] ?? null,
            'utm_medium' => $data['utm_medium'] ?? null,
            'utm_campaign' => $data['utm_campaign'] ?? null,
            'utm_term' => $data['utm_term'] ?? null,
            'utm_content' => $data['utm_content'] ?? null,
            'referrer' => $data['referrer'] ?? null,
        ], fn (mixed $value): bool => $value !== null && $value !== '');

        if ($attribution === []) {
            $attribution = LeadAttribution::resolve($request, $listing);
        }

        $createInquiry->handle(
            $listing,
            array_merge($data, $attribution),
            $request->ip(),
        );

        return to_route('public.bookings.availability', ['listing' => $listing->slug])
            ->with('inquiry_submitted', 'Recibimos tu solicitud. El anfitrión se pondrá en contacto contigo.');
    }
}
