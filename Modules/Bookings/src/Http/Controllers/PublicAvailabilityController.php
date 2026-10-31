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

        $today = CarbonImmutable::now($listing->timezone)->toDateString();
        $end = CarbonImmutable::now($listing->timezone)
            ->addDays((int) config('bookings.public_calendar_days', 90))
            ->toDateString();

        return Inertia::render('Bookings/Public/Availability', [
            'listing' => ListingPresenter::publicDetail($listing),
            'blocked_dates' => $availability->blockedDates($listing, $today, $end),
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

        $createInquiry->handle($listing, $request->validated(), $request->ip());

        return to_route('public.bookings.availability', ['listing' => $listing->slug])
            ->with('inquiry_submitted', 'Recibimos tu solicitud. El anfitrión se pondrá en contacto contigo.');
    }
}
