<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use App\Support\Branding;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Notifications\InquiryReceivedVisitorNotification;
use Modules\Bookings\Notifications\NewInquiryDirectoryNotification;
use Modules\Bookings\Notifications\NewInquiryOwnerNotification;
use Modules\Listings\Models\Listing;
use Throwable;

final class CreateBookingInquiry
{
    public function __construct(
        private readonly RecordBookingActivity $activity,
        private readonly RecordCustomerActivity $customerActivity,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(
        Listing $listing,
        array $data,
        ?string $ipAddress,
        string $source = 'public_web',
    ): BookingInquiry {
        $data = $this->normalize($data);

        $duplicate = $source === 'listing_contact'
            ? $this->recentDuplicate($listing, $data)
            : null;

        if ($duplicate instanceof BookingInquiry) {
            return $duplicate->loadMissing(['listing.team', 'listing.media']);
        }

        $inquiry = DB::transaction(function () use ($listing, $data, $ipAddress, $source): BookingInquiry {
            $customer = Customer::query()
                ->where('team_id', $listing->team_id)
                ->where('email', (string) $data['email'])
                ->first();

            if (! $customer) {
                $customer = new Customer([
                    'name' => (string) $data['name'],
                    'email' => (string) $data['email'],
                    'phone' => filled($data['phone'] ?? null) ? $data['phone'] : null,
                    'whatsapp_phone' => $data['whatsapp_phone'] ?? null,
                ]);
                $customer->team_id = $listing->team_id;
                $customer->listing_id = $listing->id;
                $customer->save();
            } elseif (blank($customer->phone) && filled($data['phone'] ?? null)) {
                $customer->update(['phone' => $data['phone']]);
            }

            $inquiry = new BookingInquiry($data);
            $inquiry->team_id = $listing->team_id;
            $inquiry->listing_id = $listing->id;
            $inquiry->customer_id = $customer->id;
            $inquiry->status = InquiryStatus::New;
            $inquiry->source = $source;
            $inquiry->consent_at = now();
            $inquiry->ip_hash = $ipAddress
                ? hash_hmac('sha256', $ipAddress, (string) config('app.key'))
                : null;
            $inquiry->save();

            return $inquiry;
        });

        $inquiry->loadMissing(['listing.team', 'listing.media']);

        $this->activity->handle($inquiry, 'booking.created', null, [
            'source' => $source,
            'date_mode' => $inquiry->date_mode,
            'start_date' => $inquiry->start_date->toDateString(),
            'end_date' => $inquiry->end_date->toDateString(),
            'guest_range' => $inquiry->guest_range,
        ]);

        if ($inquiry->customer) {
            $this->customerActivity->handle(
                $inquiry->customer,
                CustomerActivityType::System,
                "Solicitud vinculada: {$inquiry->listing->name}.",
                null,
                null,
                ['booking_inquiry_id' => $inquiry->id],
            );
        }

        $this->notify($inquiry);

        return $inquiry;
    }

    /**
     * Normalize dates and optional contact fields shared by every public form.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        $dateMode = ($data['date_mode'] ?? null) === 'approximate'
            ? 'approximate'
            : 'exact';

        $data['date_mode'] = $dateMode;

        if ($dateMode === 'approximate' && filled($data['event_month'] ?? null)) {
            $month = CarbonImmutable::createFromFormat('!Y-m-d', $data['event_month'].'-01');

            if ($month instanceof CarbonImmutable) {
                $data['start_date'] = $month->startOfMonth()->toDateString();
                $data['end_date'] = $month->endOfMonth()->toDateString();
            }
        } elseif (filled($data['event_date'] ?? null)) {
            $data['start_date'] = (string) $data['event_date'];
            $data['end_date'] = (string) $data['event_date'];
        }

        if ($dateMode === 'exact') {
            $data['event_month'] = null;
        }

        $data['phone'] = filled($data['phone'] ?? null)
            ? (string) $data['phone']
            : '';

        if (filled($data['guest_range'] ?? null)) {
            $data['guests'] = null;
        }

        return $data;
    }

    /**
     * Avoid accidental duplicate submissions (double click, retried request).
     *
     * @param  array<string, mixed>  $data
     */
    private function recentDuplicate(Listing $listing, array $data): ?BookingInquiry
    {
        $window = (int) config('bookings.contact_dedupe_minutes', 10);

        if ($window <= 0) {
            return null;
        }

        return BookingInquiry::query()
            ->where('listing_id', $listing->id)
            ->where('email', (string) $data['email'])
            ->where('message', (string) ($data['message'] ?? ''))
            ->whereDate('start_date', (string) ($data['start_date'] ?? ''))
            ->whereDate('end_date', (string) ($data['end_date'] ?? ''))
            ->where('created_at', '>=', now()->subMinutes($window))
            ->latest('id')
            ->first();
    }

    private function notify(BookingInquiry $inquiry): void
    {
        try {
            Notification::route('mail', $inquiry->email)
                ->notify(new InquiryReceivedVisitorNotification($inquiry));
        } catch (Throwable $exception) {
            // The request is already persisted; a mail failure must not lose it.
            report($exception);
        }

        try {
            $team = $inquiry->listing->team;

            if ($team?->isDirectory()) {
                $support = Branding::supportEmail() ?: Branding::fromAddress();

                if (filled($support)) {
                    Notification::route('mail', $support)
                        ->notify(new NewInquiryDirectoryNotification($inquiry));
                }

                return;
            }

            $owner = $team?->owner();

            if ($owner instanceof User && $owner->wantsInquiryEmails()) {
                $owner->notify(new NewInquiryOwnerNotification($inquiry));
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
