<?php

namespace Modules\Bookings\Actions;

use App\Models\Team;
use Modules\Bookings\Models\Customer;

final class CreateCustomer
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Team $team, array $data): Customer
    {
        $customer = new Customer($data);
        $customer->team_id = $team->id;
        $customer->save();

        return $customer;
    }
}
