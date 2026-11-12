<?php

namespace Modules\Listings\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Listings\Models\Listing;
use Modules\Listings\Support\ListingPresenter;

final class PublicListingSearch
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     listings: LengthAwarePaginator<int, array<string, mixed>>,
     *     mapListings: array<int, array<string, mixed>>,
     *     center: array{lat: float, lng: float}|null,
     *     radius: float|null,
     *     bounds: array{north: float, south: float, east: float, west: float}|null
     * }
     */
    public function handle(array $filters): array
    {
        $perPage = (int) config('listings.search.per_page', 12);
        $maxMarkers = (int) config('listings.search.map_markers', 300);
        $maxCandidates = (int) config('listings.search.max_candidates', 500);
        $latitude = isset($filters['lat']) ? (float) $filters['lat'] : null;
        $longitude = isset($filters['lng']) ? (float) $filters['lng'] : null;
        $radius = isset($filters['radius'])
            ? (float) $filters['radius']
            : (float) config('listings.search.default_radius_km', 25);
        $bounds = $this->bounds($filters);

        $base = Listing::query()
            ->publiclyVisible()
            ->with('coverImage');

        $this->applyFilters($base, $filters);

        if ($bounds !== null) {
            $base
                ->whereBetween('latitude', [$bounds['south'], $bounds['north']])
                ->whereBetween('longitude', [$bounds['west'], $bounds['east']]);

            $paginator = (clone $base)
                ->orderByDesc('published_at')
                ->paginate($perPage)
                ->withQueryString();

            $paginator->through(
                fn (Listing $listing): array => ListingPresenter::publicCard($listing),
            );

            return [
                'listings' => $paginator,
                'mapListings' => $this->mapPoints($base, $maxMarkers),
                'center' => [
                    'lat' => ($bounds['north'] + $bounds['south']) / 2,
                    'lng' => ($bounds['east'] + $bounds['west']) / 2,
                ],
                'radius' => null,
                'bounds' => $bounds,
            ];
        }

        if ($latitude !== null && $longitude !== null) {
            $latitudeDelta = $radius / 111.045;
            $longitudeDelta = $radius / (111.045 * max(cos(deg2rad($latitude)), 0.01));

            $candidates = (clone $base)
                ->whereBetween('latitude', [$latitude - $latitudeDelta, $latitude + $latitudeDelta])
                ->whereBetween('longitude', [$longitude - $longitudeDelta, $longitude + $longitudeDelta])
                ->orderByDesc('published_at')
                ->limit($maxCandidates)
                ->get();

            $filtered = $candidates
                ->map(fn (Listing $listing): array => [
                    'listing' => $listing,
                    'distance' => $listing->distanceFrom($latitude, $longitude),
                ])
                ->filter(fn (array $row): bool => $row['distance'] !== null && $row['distance'] <= $radius)
                ->sortBy('distance')
                ->values();

            $page = LengthAwarePaginator::resolveCurrentPage();
            $items = $filtered
                ->forPage($page, $perPage)
                ->values()
                ->map(fn (array $row): array => ListingPresenter::publicCard(
                    $row['listing'],
                    $row['distance'],
                ))
                ->all();

            $paginator = new LengthAwarePaginator(
                $items,
                $filtered->count(),
                $perPage,
                $page,
                [
                    'path' => url()->current(),
                    'query' => $filters,
                ],
            );

            return [
                'listings' => $paginator,
                'mapListings' => $filtered
                    ->take($maxMarkers)
                    ->map(fn (array $row): array => ListingPresenter::publicMapPoint($row['listing']))
                    ->values()
                    ->all(),
                'center' => ['lat' => $latitude, 'lng' => $longitude],
                'radius' => $radius,
                'bounds' => null,
            ];
        }

        $paginator = (clone $base)
            ->orderByDesc('published_at')
            ->paginate($perPage)
            ->withQueryString();

        $paginator->through(
            fn (Listing $listing): array => ListingPresenter::publicCard($listing),
        );

        return [
            'listings' => $paginator,
            'mapListings' => $this->mapPoints($base, $maxMarkers),
            'center' => null,
            'radius' => null,
            'bounds' => null,
        ];
    }

    /**
     * @param  Builder<Listing>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['state'])) {
            $query->where('state', $filters['state']);
        }

        if (! empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (! empty($filters['postal_code'])) {
            $query->where('postal_code', (string) $filters['postal_code']);
        }

        if (! empty($filters['capacity'])) {
            $query->where('capacity', '>=', (int) $filters['capacity']);
        }

        if (! empty($filters['service'])) {
            $query->whereJsonContains('services', $filters['service']);
        }

        if (isset($filters['price_min']) && is_numeric($filters['price_min'])) {
            $query
                ->whereNotNull('price_from_cents')
                ->where('price_from_cents', '>=', (int) $filters['price_min'] * 100);
        }

        if (isset($filters['price_max']) && is_numeric($filters['price_max'])) {
            $query
                ->whereNotNull('price_from_cents')
                ->where('price_from_cents', '<=', (int) $filters['price_max'] * 100);
        }
    }

    /**
     * @param  Builder<Listing>  $query
     * @return array<int, array<string, mixed>>
     */
    private function mapPoints(Builder $query, int $maxMarkers): array
    {
        return (clone $query)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderByDesc('published_at')
            ->limit($maxMarkers)
            ->get()
            ->map(fn (Listing $listing): array => ListingPresenter::publicMapPoint($listing))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{north: float, south: float, east: float, west: float}|null
     */
    private function bounds(array $filters): ?array
    {
        foreach (['north', 'south', 'east', 'west'] as $key) {
            if (! isset($filters[$key]) || $filters[$key] === '') {
                return null;
            }
        }

        return [
            'north' => (float) $filters['north'],
            'south' => (float) $filters['south'],
            'east' => (float) $filters['east'],
            'west' => (float) $filters['west'],
        ];
    }
}
