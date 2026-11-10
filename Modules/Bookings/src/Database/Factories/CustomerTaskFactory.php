<?php

namespace Modules\Bookings\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Bookings\Enums\CustomerTaskPriority;
use Modules\Bookings\Enums\CustomerTaskStatus;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerTask;

/**
 * @extends Factory<CustomerTask>
 */
class CustomerTaskFactory extends Factory
{
    protected $model = CustomerTask::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Customer::factory(),
            'customer_id' => Customer::factory(),
            'assigned_to' => User::factory(),
            'created_by' => User::factory(),
            'title' => fake()->sentence(4),
            'notes' => fake()->optional()->sentence(),
            'due_at' => now()->addDays(2),
            'priority' => CustomerTaskPriority::Normal,
            'status' => CustomerTaskStatus::Open,
            'completed_at' => null,
        ];
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (): array => [
            'team_id' => $customer->team_id,
            'customer_id' => $customer->id,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => CustomerTaskStatus::Done,
            'completed_at' => now(),
        ]);
    }
}
