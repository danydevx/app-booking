<?php

namespace Modules\Bookings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\Bookings\Actions\CreateBookingInquiry;
use Modules\Bookings\Http\Requests\StoreListingContactRequest;
use Modules\Bookings\Support\LeadAttribution;
use Modules\Listings\Models\Listing;

class PublicListingContactController extends Controller
{
    public function store(
        StoreListingContactRequest $request,
        Listing $listing,
        CreateBookingInquiry $createInquiry,
    ): RedirectResponse {
        abort_unless($listing->isPubliclyVisible(), 404);

        $data = $request->validated();
        $attribution = LeadAttribution::resolve($request, $listing);

        $createInquiry->handle(
            $listing,
            array_merge($data, $attribution),
            $request->ip(),
            'listing_contact',
        );

        return to_route('public.listings.show', ['listing' => $listing->slug]);
    }
}
