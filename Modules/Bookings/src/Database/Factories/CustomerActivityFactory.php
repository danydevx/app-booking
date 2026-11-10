<?php

namespace Modules\Bookings\Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerActivity;

/**
 * @extends Factory<CustomerActivity>
 */
class CustomerActivityFactory extends Factory
{
    protected $model = CustomerActivity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'type' => CustomerActivityType::Note,
            'body' => fake()->sentence(),
            'occurred_at' => now(),
            'metadata' => null,
        ];
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (): array => [
            'team_id' => $customer->team_id,
            'customer_id' => $customer->id,
        ]);
    }
}
