<?php

namespace Modules\Bookings\Actions;

use App\Models\Team;
use Modules\Bookings\Models\Customer;
use Modules\Listings\Models\Listing;

final class CreateCustomer
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Team $team, array $data, ?Listing $listing = null): Customer
    {
        $customer = new Customer($data);
        $customer->team_id = $team->id;

        if ($listing !== null) {
            $customer->listing_id = $listing->id;
        }

        $customer->save();

        return $customer;
    }
}
