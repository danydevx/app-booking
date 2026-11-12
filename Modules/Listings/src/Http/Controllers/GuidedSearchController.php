<?php

namespace Modules\Listings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Branding;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Bookings\Support\BookingOptionCatalog;
use Modules\Listings\Http\Requests\PublicListingSearchRequest;
use Modules\Listings\Models\Listing;
use Modules\Listings\Queries\PublicListingSearch;
use Modules\Listings\Support\ListingPublicCatalog;
use Modules\Platform\Support\GeographicCoverageService;

class GuidedSearchController extends Controller
{
    public function index(GeographicCoverageService $coverage): Response
    {
        $timezone = (string) config('listings.default_timezone', 'America/Mexico_City');
        $today = CarbonImmutable::now($timezone);

        $centers = Listing::query()
            ->publiclyVisible()
            ->whereNotNull('state')
            ->whereNotNull('city')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw('state, city, AVG(latitude) as latitude, AVG(longitude) as longitude, COUNT(*) as listings_count')
            ->groupBy('state', 'city')
            ->orderBy('state')
            ->orderBy('city')
            ->get()
            ->map(fn (Listing $row): array => [
                'state' => (string) $row->state,
                'city' => (string) $row->city,
                'latitude' => (float) $row->latitude,
                'longitude' => (float) $row->longitude,
                'listings_count' => (int) $row->getAttribute('listings_count'),
            ])
            ->values()
            ->all();

        return Inertia::render('Listings/Public/GuidedSearch', [
            'locations' => $coverage->locations(),
            'municipalityCenters' => $centers,
            'featuredCharacteristics' => ListingPublicCatalog::featuredCharacteristics(),
            'eventTypes' => ListingPublicCatalog::activeEventTypes(),
            'contact' => [
                'guest_ranges' => BookingOptionCatalog::guestRanges(),
                'privacy_url' => Branding::legalLinks()['privacy'] ?? null,
                'default_message' => (string) config('bookings.contact_default_message'),
                'max_message_length' => 2000,
                'today' => $today->toDateString(),
                'current_month' => $today->format('Y-m'),
            ],
            'urls' => [
                'home' => route('home'),
                'index' => route('public.listings.index'),
                'results' => route('public.guided-search.results'),
            ],
        ]);
    }

    public function results(
        PublicListingSearchRequest $request,
        PublicListingSearch $search,
    ): JsonResponse {
        $filters = $request->validated();
        $result = $search->handle($filters);
        $paginator = $result['listings'];

        $paginator->through(function (array $card): array {
            $card['contact_url'] = route('public.listings.contact.store', [
                'listing' => $card['slug'],
            ]);

            return $card;
        });

        return response()->json([
            'listings' => $paginator->items(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'map_listings' => $result['mapListings'],
            'center' => $result['center'],
            'radius' => $result['radius'],
            'filters' => $filters,
        ]);
    }
}
