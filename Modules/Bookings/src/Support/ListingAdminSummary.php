<?php

namespace Modules\Bookings\Support;

use Carbon\CarbonImmutable;
use Modules\Bookings\Enums\AvailabilityStatus;
use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\ListingUnavailability;
use Modules\Listings\Models\Listing;

final class ListingAdminSummary
{
    public function __construct(
        private readonly AvailabilityService $availability,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function for(Listing $listing, string $teamSlug): array
    {
        $today = CarbonImmutable::now($listing->timezone)->toDateString();
        $end = CarbonImmutable::now($listing->timezone)->addDays(30)->toDateString();
        $inquiries = BookingInquiry::query()->where('listing_id', $listing->id);
        $customers = Customer::query()
            ->where('team_id', $listing->team_id)
            ->whereHas('inquiries', fn ($query) => $query->where('listing_id', $listing->id));

        $upcomingQuery = BookingInquiry::query()
            ->where('listing_id', $listing->id)
            ->where('status', InquiryStatus::Accepted)
            ->where('start_date', '>=', $today);

        $upcomingCount = (clone $upcomingQuery)->count();

        $upcoming = (clone $upcomingQuery)
            ->orderBy('start_date')
            ->limit(5)
            ->get()
            ->map(fn (BookingInquiry $inquiry): array => BookingPresenter::inquiryCard($inquiry, $teamSlug))
            ->all();

        $recent = BookingInquiry::query()
            ->where('listing_id', $listing->id)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (BookingInquiry $inquiry): array => BookingPresenter::inquiryCard($inquiry, $teamSlug))
            ->all();

        $customerRows = (clone $customers)
            ->withCount([
                'inquiries as listing_inquiries_count' => fn ($query) => $query->where('listing_id', $listing->id),
            ])
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map(fn (Customer $customer): array => [
                ...BookingPresenter::customerCard($customer, $teamSlug, $listing->slug),
                'listing_inquiries_count' => (int) $customer->getAttribute('listing_inquiries_count'),
            ])
            ->all();

        return [
            'stats' => [
                'inquiries' => (clone $inquiries)->count(),
                'new_inquiries' => (clone $inquiries)->where('status', InquiryStatus::New)->count(),
                'accepted_inquiries' => (clone $inquiries)->where('status', InquiryStatus::Accepted)->count(),
                'upcoming_reservations' => $upcomingCount,
                'customers' => (clone $customers)->count(),
                'blocked_dates_30d' => $this->availability->unavailableBetween(
                    $listing,
                    $today,
                    $end,
                )->filter(fn (ListingUnavailability $date): bool => $date->status === AvailabilityStatus::Blocked)->count(),
            ],
            'upcomingReservations' => $upcoming,
            'recentInquiries' => $recent,
            'customers' => $customerRows,
            'blockedDates' => array_slice(
                $this->availability->blockedDates($listing, $today, $end),
                0,
                6,
            ),
        ];
    }
}
