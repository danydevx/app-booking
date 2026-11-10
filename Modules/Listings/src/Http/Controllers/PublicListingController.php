<?php

namespace Modules\Listings\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Bookings\Support\AvailabilityService;
use Modules\Bookings\Support\LeadAttribution;
use Modules\Listings\Http\Requests\PublicListingSearchRequest;
use Modules\Listings\Models\Listing;
use Modules\Listings\Queries\PublicListingSearch;
use Modules\Listings\Support\ListingPresenter;
use Modules\Listings\Support\MexicoLocationCatalog;
use Modules\Listings\Support\TerraceServiceCatalog;

class PublicListingController extends Controller
{
    public function index(
        PublicListingSearchRequest $request,
        PublicListingSearch $search,
    ): Response {
        $result = $search->handle($request->validated());

        return Inertia::render('Listings/Public/Index', [
            'listings' => $result['listings'],
            'center' => $result['center'],
            'radius' => $result['radius'],
            'filters' => $request->validated(),
            'priorityServices' => TerraceServiceCatalog::priorityOptions(),
            'additionalServices' => TerraceServiceCatalog::additionalOptions(),
            'locations' => MexicoLocationCatalog::all(),
            'urls' => [
                'index' => route('public.listings.index'),
            ],
        ]);
    }

    public function show(Request $request, Listing $listing, AvailabilityService $availability): Response
    {
        abort_unless($listing->isPubliclyVisible(), 404);

        $listing->load(['media', 'packages', 'commercialServices', 'promotions']);

        LeadAttribution::capture($request, $listing);

        $today = CarbonImmutable::now($listing->timezone)->toDateString();
        $end = CarbonImmutable::now($listing->timezone)->addDays(90)->toDateString();

        return Inertia::render('Listings/Public/Show', [
            'listing' => ListingPresenter::publicDetail($listing),
            'unavailableDates' => array_slice(
                $availability->blockedDates($listing, $today, $end),
                0,
                8,
            ),
            'urls' => [
                'index' => route('public.listings.index'),
                'report' => route('public.reports.create', ['listing' => $listing->slug]),
            ],
        ]);
    }
}
