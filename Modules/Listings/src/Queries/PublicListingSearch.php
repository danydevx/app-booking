<?php

namespace Modules\Listings\Queries;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Listings\Models\Listing;
use Modules\Listings\Support\ListingPresenter;

final class PublicListingSearch
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{listings: LengthAwarePaginator<int, array<string, mixed>>, center: array{lat: float, lng: float}|null, radius: float|null}
     */
    public function handle(array $filters): array
    {
        $perPage = (int) config('listings.search.per_page', 12);
        $maxCandidates = (int) config('listings.search.max_candidates', 500);
        $latitude = isset($filters['lat']) ? (float) $filters['lat'] : null;
        $longitude = isset($filters['lng']) ? (float) $filters['lng'] : null;
        $radius = isset($filters['radius'])
            ? (float) $filters['radius']
            : (float) config('listings.search.default_radius_km', 25);

        $query = Listing::query()
            ->publiclyVisible()
            ->with('coverImage');

        if (! empty($filters['city'])) {
            $query->where('city', 'like', '%'.$filters['city'].'%');
        }

        if (! empty($filters['capacity'])) {
            $query->where('capacity', '>=', (int) $filters['capacity']);
        }

        if (! empty($filters['service'])) {
            $query->whereJsonContains('services', $filters['service']);
        }

        if ($latitude === null || $longitude === null) {
            $paginator = $query
                ->orderByDesc('published_at')
                ->paginate($perPage)
                ->withQueryString();

            $paginator->through(
                fn (Listing $listing): array => ListingPresenter::publicCard($listing),
            );

            return [
                'listings' => $paginator,
                'center' => null,
                'radius' => null,
            ];
        }

        $latitudeDelta = $radius / 111.045;
        $longitudeDelta = $radius / (111.045 * max(cos(deg2rad($latitude)), 0.01));

        $candidates = $query
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
            'center' => ['lat' => $latitude, 'lng' => $longitude],
            'radius' => $radius,
        ];
    }
}
