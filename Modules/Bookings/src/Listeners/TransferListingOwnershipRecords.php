<?php

namespace Modules\Bookings\Listeners;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Bookings\Models\AvailabilityBlockSeries;
use Modules\Bookings\Models\BookingContract;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerActivity;
use Modules\Bookings\Models\CustomerQuote;
use Modules\Bookings\Models\CustomerQuoteItem;
use Modules\Bookings\Models\CustomerQuoteStatusHistory;
use Modules\Bookings\Models\CustomerTask;
use Modules\Listings\Events\ListingOwnershipTransferred;

final class TransferListingOwnershipRecords
{
    public function handle(ListingOwnershipTransferred $event): void
    {
        $fromTeamId = $event->fromTeamId;
        $toTeamId = $event->toTeamId;

        if ($fromTeamId === $toTeamId) {
            return;
        }

        $listingId = $event->listing->id;

        DB::transaction(function () use ($listingId, $fromTeamId, $toTeamId): void {
            $touchedCustomerIds = $this->moveCustomers($listingId, $fromTeamId, $toTeamId);

            $inquiryIds = BookingInquiry::query()
                ->where('listing_id', $listingId)
                ->where('team_id', $fromTeamId)
                ->pluck('id')
                ->all();

            if ($inquiryIds !== []) {
                BookingInquiry::query()
                    ->whereIn('id', $inquiryIds)
                    ->update(['team_id' => $toTeamId]);

                BookingContract::query()
                    ->whereIn('booking_inquiry_id', $inquiryIds)
                    ->where('team_id', $fromTeamId)
                    ->update(['team_id' => $toTeamId]);
            }

            $quoteIds = CustomerQuote::query()
                ->where('listing_id', $listingId)
                ->where('team_id', $fromTeamId)
                ->pluck('id')
                ->all();

            if ($quoteIds !== []) {
                CustomerQuote::query()
                    ->whereIn('id', $quoteIds)
                    ->update(['team_id' => $toTeamId]);

                CustomerQuoteItem::query()
                    ->whereIn('customer_quote_id', $quoteIds)
                    ->where('team_id', $fromTeamId)
                    ->update(['team_id' => $toTeamId]);

                CustomerQuoteStatusHistory::query()
                    ->whereIn('customer_quote_id', $quoteIds)
                    ->where('team_id', $fromTeamId)
                    ->update(['team_id' => $toTeamId]);
            }

            if ($touchedCustomerIds !== []) {
                CustomerActivity::query()
                    ->whereIn('customer_id', $touchedCustomerIds)
                    ->where('team_id', $fromTeamId)
                    ->update(['team_id' => $toTeamId]);

                CustomerTask::query()
                    ->whereIn('customer_id', $touchedCustomerIds)
                    ->where('team_id', $fromTeamId)
                    ->update(['team_id' => $toTeamId]);
            }

            AvailabilityBlockSeries::query()
                ->where('listing_id', $listingId)
                ->where('team_id', $fromTeamId)
                ->update(['team_id' => $toTeamId]);
        });
    }

    /**
     * Move the customers of the listing, merging by email when the target
     * account already has a customer with the same address.
     *
     * @return array<int, int> customer ids whose activities and tasks moved
     */
    private function moveCustomers(int $listingId, int $fromTeamId, int $toTeamId): array
    {
        $touched = [];

        /** @var Collection<int, Customer> $customers */
        $customers = Customer::query()
            ->where('listing_id', $listingId)
            ->where('team_id', $fromTeamId)
            ->get();

        foreach ($customers as $customer) {
            $existing = Customer::query()
                ->where('team_id', $toTeamId)
                ->where('email', $customer->email)
                ->first();

            if ($existing === null) {
                $customer->forceFill(['team_id' => $toTeamId])->save();
                $touched[] = $customer->id;
            } else {
                BookingInquiry::query()
                    ->where('customer_id', $customer->id)
                    ->update(['customer_id' => $existing->id]);

                CustomerQuote::query()
                    ->where('customer_id', $customer->id)
                    ->update(['customer_id' => $existing->id]);

                CustomerActivity::query()
                    ->where('customer_id', $customer->id)
                    ->update(['customer_id' => $existing->id]);

                CustomerTask::query()
                    ->where('customer_id', $customer->id)
                    ->update(['customer_id' => $existing->id]);

                $customer->delete();
                $touched[] = $existing->id;
            }

            // Tags belong to the source account; the target account has its own
            // catalog, so assignments would point at foreign tags.
            DB::table('customer_tag_assignments')
                ->where('customer_id', $customer->id)
                ->delete();
        }

        return array_values(array_unique($touched));
    }
}
