<?php

namespace Modules\Listings\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Bookings\Support\AvailabilityService;
use Modules\Bookings\Support\LeadAttribution;
use Modules\Listings\Actions\ResolveSearchLocation;
use Modules\Listings\Http\Requests\PublicListingSearchRequest;
use Modules\Listings\Http\Requests\PublicLocationSuggestionsRequest;
use Modules\Listings\Models\Listing;
use Modules\Listings\Queries\PublicListingSearch;
use Modules\Listings\Support\ListingPresenter;
use Modules\Listings\Support\MexicoLocationCatalog;
use Modules\Listings\Support\TerraceServiceCatalog;

class PublicListingController extends Controller
{
    public function home(): Response
    {
        $featured = Listing::query()
            ->publiclyVisible()
            ->with('coverImage')
            ->orderByDesc('published_at')
            ->limit(8)
            ->get()
            ->map(fn (Listing $listing): array => ListingPresenter::publicCard($listing))
            ->values()
            ->all();

        $cities = Listing::query()
            ->publiclyVisible()
            ->whereNotNull('city')
            ->select('state', 'city')
            ->selectRaw('count(*) as listings_count')
            ->groupBy('state', 'city')
            ->orderByDesc('listings_count')
            ->orderBy('city')
            ->limit(8)
            ->get()
            ->map(fn (Listing $row): array => [
                'state' => (string) $row->state,
                'city' => (string) $row->city,
                'count' => (int) ($row->getAttribute('listings_count') ?? 0),
            ])
            ->values()
            ->all();

        return Inertia::render('Listings/Public/Home', [
            'listings' => $featured,
            'cities' => $cities,
            'locations' => MexicoLocationCatalog::all(),
            'services' => TerraceServiceCatalog::options(),
            'totalListings' => Listing::query()->publiclyVisible()->count(),
            'urls' => [
                'index' => route('public.listings.index'),
            ],
        ]);
    }

    public function index(
        PublicListingSearchRequest $request,
        PublicListingSearch $search,
        ResolveSearchLocation $resolveLocation,
    ): Response {
        $filters = $request->validated();
        $query = trim((string) ($filters['q'] ?? ''));

        if ($query !== '') {
            $hasStructuredLocation = ! empty($filters['state'])
                || ! empty($filters['city'])
                || ! empty($filters['postal_code']);

            if (! $hasStructuredLocation) {
                $resolved = $resolveLocation->handle($query);

                if ($resolved === null) {
                    throw ValidationException::withMessages([
                        'location' => 'No encontramos esa ubicación. Prueba con un municipio, estado o código postal, o busca directamente en el mapa.',
                    ]);
                }

                $filters = array_merge($filters, $resolved);
            }
        }

        $result = $search->handle($filters);

        return Inertia::render('Listings/Public/Index', [
            'listings' => $result['listings'],
            'mapListings' => $result['mapListings'],
            'center' => $result['center'],
            'radius' => $result['radius'],
            'bounds' => $result['bounds'],
            'filters' => $filters,
            'priorityServices' => TerraceServiceCatalog::priorityOptions(),
            'additionalServices' => TerraceServiceCatalog::additionalOptions(),
            'services' => TerraceServiceCatalog::options(),
            'locations' => MexicoLocationCatalog::all(),
            'urls' => [
                'index' => route('public.listings.index'),
                'locations' => route('public.listings.locations'),
            ],
        ]);
    }

    public function locations(PublicLocationSuggestionsRequest $request): JsonResponse
    {
        $query = trim((string) $request->string('q'));

        $suggestions = Cache::remember(
            'public:location-suggestions:'.md5(Str::lower(Str::ascii($query))),
            now()->addSeconds((int) config('listings.search.suggestions_cache_seconds', 60)),
            fn (): array => $this->locationSuggestions($query),
        );

        return response()->json(['suggestions' => $suggestions]);
    }

    /**
     * @return array<int, array{type: string, label: string, sublabel: string, params: array<string, string>}>
     */
    private function locationSuggestions(string $query): array
    {
        $normalized = Str::lower(Str::ascii($query));
        $suggestions = [];

        foreach (MexicoLocationCatalog::all() as $value => $state) {
            foreach ($state['municipalities'] as $city) {
                $candidate = Str::lower(Str::ascii($city));

                if (! str_starts_with($candidate, $normalized)) {
                    continue;
                }

                $suggestions[] = [
                    'type' => 'city',
                    'label' => $city,
                    'sublabel' => $state['label'],
                    'params' => ['state' => $value, 'city' => $city],
                    'score' => $candidate === $normalized ? 0 : 1,
                ];
            }

            $stateCandidate = Str::lower(Str::ascii((string) $state['label']));

            if (
                str_starts_with($stateCandidate, $normalized)
                || str_starts_with(Str::lower(Str::ascii((string) $value)), $normalized)
            ) {
                $suggestions[] = [
                    'type' => 'state',
                    'label' => $state['label'],
                    'sublabel' => 'Estado',
                    'params' => ['state' => $value],
                    'score' => $stateCandidate === $normalized ? 0 : 2,
                ];
            }
        }

        if (preg_match('/^\d{2,6}$/', $query) === 1) {
            $postalCodes = Listing::query()
                ->publiclyVisible()
                ->whereNotNull('postal_code')
                ->where('postal_code', 'like', $query.'%')
                ->select('postal_code', 'city', 'state')
                ->distinct()
                ->orderBy('postal_code')
                ->limit(4)
                ->get();

            foreach ($postalCodes as $row) {
                $place = collect([$row->city, $row->state])->filter()->implode(', ');

                $suggestions[] = [
                    'type' => 'postal_code',
                    'label' => (string) $row->postal_code,
                    'sublabel' => $place !== '' ? 'Código postal · '.$place : 'Código postal',
                    'params' => ['postal_code' => (string) $row->postal_code],
                    'score' => 1,
                ];
            }
        }

        usort(
            $suggestions,
            fn (array $a, array $b): int => [$a['score'], $a['label']] <=> [$b['score'], $b['label']],
        );

        $unique = [];

        foreach ($suggestions as $suggestion) {
            $key = $suggestion['type'].':'.$suggestion['label'];

            if (isset($unique[$key])) {
                continue;
            }

            unset($suggestion['score']);
            $unique[$key] = $suggestion;
        }

        return array_slice(array_values($unique), 0, 8);
    }

    public function show(Request $request, Listing $listing, AvailabilityService $availability): Response
    {
        abort_unless($listing->isPubliclyVisible(), 404);

        $listing->load(['media', 'packages', 'commercialServices', 'promotions', 'priceRanges']);

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
