<?php

namespace Modules\Bookings\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Bookings\Enums\DepositMethod;
use Modules\Bookings\Enums\DepositStatus;
use Modules\Bookings\Models\BookingDeposit;
use Modules\Bookings\Models\BookingInquiry;

/**
 * @extends Factory<BookingDeposit>
 */
class BookingDepositFactory extends Factory
{
    protected $model = BookingDeposit::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_inquiry_id' => BookingInquiry::factory(),
            'amount_cents' => 100000,
            'currency' => 'MXN',
            'status' => DepositStatus::Pending,
            'method' => DepositMethod::Cash,
            'reference' => null,
            'received_at' => null,
            'notes' => null,
        ];
    }

    public function received(): static
    {
        return $this->state(fn (): array => [
            'status' => DepositStatus::Received,
            'received_at' => now(),
        ]);
    }
}
