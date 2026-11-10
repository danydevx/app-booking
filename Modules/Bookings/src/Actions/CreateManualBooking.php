<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Notifications\ManualBookingConfirmation;
use Modules\Bookings\Support\AvailabilityService;
use Modules\Listings\Models\Listing;

final class CreateManualBooking
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly ReserveListingDates $reserveDates,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(
        Listing $listing,
        User $user,
        array $data,
        bool $notifyCustomer = true,
    ): BookingInquiry {
        try {
            $booking = DB::transaction(function () use ($listing, $user, $data): BookingInquiry {
                $lockedListing = Listing::query()
                    ->whereKey($listing->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $conflicts = $this->availability->conflictingDates(
                    $lockedListing,
                    (string) $data['start_date'],
                    (string) $data['end_date'],
                );

                if ($conflicts !== []) {
                    throw ValidationException::withMessages([
                        'dates' => 'Algunas fechas ya no están disponibles: '.implode(', ', $conflicts),
                    ]);
                }

                $customer = Customer::query()
                    ->where('team_id', $lockedListing->team_id)
                    ->where('email', (string) $data['email'])
                    ->first();

                if (! $customer) {
                    $customer = new Customer([
                        'name' => (string) $data['name'],
                        'email' => (string) $data['email'],
                        'phone' => $data['phone'] ?? null,
                        'whatsapp_phone' => $data['whatsapp_phone'] ?? null,
                    ]);
                    $customer->team_id = $lockedListing->team_id;
                    $customer->save();
                } else {
                    $fill = [];

                    if (blank($customer->phone) && filled($data['phone'] ?? null)) {
                        $fill['phone'] = $data['phone'];
                    }

                    if (blank($customer->whatsapp_phone) && filled($data['whatsapp_phone'] ?? null)) {
                        $fill['whatsapp_phone'] = $data['whatsapp_phone'];
                    }

                    if ($fill !== []) {
                        $customer->update($fill);
                    }
                }

                $inquiry = new BookingInquiry($data);
                $inquiry->team_id = $lockedListing->team_id;
                $inquiry->listing_id = $lockedListing->id;
                $inquiry->customer_id = $customer->id;
                $inquiry->status = InquiryStatus::Accepted;
                $inquiry->source = 'manual';
                $inquiry->consent_at = now();
                $inquiry->accepted_at = now();
                $inquiry->save();

                $this->reserveDates->handle($inquiry, $user, 'Reserva manual');

                return $inquiry->refresh();
            });

            $booking->loadMissing('listing');

            if ($notifyCustomer) {
                Notification::route('mail', $booking->email)
                    ->notify(new ManualBookingConfirmation($booking));
            }

            return $booking;
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000' || str_contains($exception->getMessage(), 'UNIQUE')) {
                throw ValidationException::withMessages([
                    'dates' => 'Algunas fechas ya no están disponibles.',
                ]);
            }

            throw $exception;
        }
    }
}
