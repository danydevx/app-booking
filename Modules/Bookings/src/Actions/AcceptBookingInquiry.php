<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Notifications\InquiryAcceptedVisitorNotification;
use Modules\Bookings\Support\AvailabilityService;

final class AcceptBookingInquiry
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly ReserveListingDates $reserveDates,
        private readonly RecordBookingActivity $activity,
    ) {}

    public function handle(BookingInquiry $inquiry, User $user): BookingInquiry
    {
        $acceptedNow = false;

        try {
            $accepted = DB::transaction(function () use ($inquiry, $user, &$acceptedNow): BookingInquiry {
                $locked = BookingInquiry::query()
                    ->whereKey($inquiry->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($locked->status === InquiryStatus::Accepted) {
                    return $locked;
                }

                if (! in_array($locked->status, [InquiryStatus::New, InquiryStatus::Contacted], true)) {
                    throw ValidationException::withMessages([
                        'status' => 'Solo puedes aceptar solicitudes nuevas o contactadas.',
                    ]);
                }

                $conflicts = $this->availability->conflictingDates(
                    $locked->listing,
                    $locked->start_date->toDateString(),
                    $locked->end_date->toDateString(),
                );

                if ($conflicts !== []) {
                    throw ValidationException::withMessages([
                        'dates' => 'Algunas fechas ya no están disponibles: '.implode(', ', $conflicts),
                    ]);
                }

                $this->reserveDates->handle($locked, $user);

                $locked->forceFill([
                    'status' => InquiryStatus::Accepted,
                    'accepted_at' => now(),
                ])->save();

                $acceptedNow = true;

                return $locked->refresh();
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000' || str_contains($exception->getMessage(), 'UNIQUE')) {
                throw ValidationException::withMessages([
                    'dates' => 'Algunas fechas ya no están disponibles.',
                ]);
            }

            throw $exception;
        }

        if ($acceptedNow) {
            $accepted->loadMissing('listing');

            $this->activity->handle($accepted, 'booking.accepted', $user, [
                'start_date' => $accepted->start_date->toDateString(),
                'end_date' => $accepted->end_date->toDateString(),
            ]);

            Notification::route('mail', $accepted->email)
                ->notify(new InquiryAcceptedVisitorNotification($accepted));
        }

        return $accepted;
    }
}
