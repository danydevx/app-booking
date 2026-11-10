<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Notifications\BookingRescheduledConfirmation;
use Modules\Bookings\Support\AvailabilityService;

final class UpdateBookingInquiry
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly ReleaseInquiryDates $releaseDates,
        private readonly ReserveListingDates $reserveDates,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(
        BookingInquiry $inquiry,
        User $user,
        array $data,
        bool $notifyCustomer = true,
    ): BookingInquiry {
        $datesChanged = false;

        try {
            $booking = DB::transaction(function () use ($inquiry, $user, $data, &$datesChanged): BookingInquiry {
                $locked = BookingInquiry::query()
                    ->whereKey($inquiry->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (in_array($locked->status, [
                    InquiryStatus::Declined,
                    InquiryStatus::Cancelled,
                    InquiryStatus::Expired,
                ], true)) {
                    throw ValidationException::withMessages([
                        'status' => 'No puedes editar una solicitud cerrada.',
                    ]);
                }

                $datesChanged = $locked->start_date->toDateString() !== (string) $data['start_date']
                    || $locked->end_date->toDateString() !== (string) $data['end_date'];

                if ($datesChanged && $locked->status === InquiryStatus::Accepted) {
                    $conflicts = $this->availability->conflictingDates(
                        $locked->listing,
                        (string) $data['start_date'],
                        (string) $data['end_date'],
                        $locked->id,
                    );

                    if ($conflicts !== []) {
                        throw ValidationException::withMessages([
                            'dates' => 'Algunas fechas ya no están disponibles: '.implode(', ', $conflicts),
                        ]);
                    }

                    $this->releaseDates->handle($locked);
                }

                $locked->fill($data);

                if ($datesChanged && $locked->status === InquiryStatus::Accepted) {
                    $locked->reminder_sent_at = null;
                }

                $locked->save();

                if ($datesChanged && $locked->status === InquiryStatus::Accepted) {
                    $this->reserveDates->handle($locked, $user, 'Reserva reprogramada');
                }

                $this->syncCustomer($locked, $data);

                return $locked->refresh();
            });

            $booking->loadMissing('listing');

            if ($notifyCustomer && $datesChanged && $booking->status === InquiryStatus::Accepted) {
                Notification::route('mail', $booking->email)
                    ->notify(new BookingRescheduledConfirmation($booking));
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

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncCustomer(BookingInquiry $inquiry, array $data): void
    {
        $customer = $inquiry->customer;

        if (! $customer) {
            return;
        }

        $customer->update([
            'name' => (string) $data['name'],
            'phone' => $data['phone'] ?? null,
            'whatsapp_phone' => $data['whatsapp_phone'] ?? null,
        ]);
    }
}
