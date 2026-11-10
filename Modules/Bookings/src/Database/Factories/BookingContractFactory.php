<?php

namespace Modules\Bookings\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Bookings\Enums\ContractStatus;
use Modules\Bookings\Models\BookingContract;
use Modules\Bookings\Models\BookingInquiry;

/**
 * @extends Factory<BookingContract>
 */
class BookingContractFactory extends Factory
{
    protected $model = BookingContract::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => BookingInquiry::factory(),
            'booking_inquiry_id' => BookingInquiry::factory(),
            'created_by' => null,
            'title' => 'Contrato de renta',
            'body' => "Términos y condiciones de la renta.\n\nEl anticipo no es reembolsable.",
            'status' => ContractStatus::Draft,
            'sent_at' => null,
            'accepted_at' => null,
            'accepted_by_name' => null,
            'accepted_ip_hash' => null,
        ];
    }

    public function forInquiry(BookingInquiry $inquiry): static
    {
        return $this->state(fn (): array => [
            'team_id' => $inquiry->team_id,
            'booking_inquiry_id' => $inquiry->id,
        ]);
    }
}
