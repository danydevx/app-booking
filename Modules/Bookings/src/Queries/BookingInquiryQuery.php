<?php

namespace Modules\Bookings\Queries;

use App\Models\Team;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Support\BookingPresenter;
use Modules\Listings\Models\Listing;

final class BookingInquiryQuery
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(Team $team, array $filters): LengthAwarePaginator
    {
        $inquiries = BookingInquiry::query()
            ->where('team_id', $team->id)
            ->with(['listing', 'customer'])
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['listing'] ?? null, fn ($query, string $listing) => $query
                ->whereHas('listing', fn ($listingQuery) => $listingQuery->where('slug', $listing)))
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate((int) config('bookings.per_page', 15))
            ->withQueryString();

        return $inquiries->through(
            fn (BookingInquiry $inquiry): array => BookingPresenter::inquiryCard($inquiry, $team->slug),
        );
    }

    /**
     * @return array<int, array{id: int, name: string, slug: string, availability_url: string}>
     */
    public function listings(Team $team): array
    {
        return Listing::query()
            ->where('team_id', $team->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Listing $listing): array => [
                'id' => $listing->id,
                'name' => $listing->name,
                'slug' => $listing->slug,
                'availability_url' => route('bookings.availability.edit', [
                    'current_team' => $team->slug,
                    'listing' => $listing->slug,
                ]),
            ])
            ->values()
            ->all();
    }
}
