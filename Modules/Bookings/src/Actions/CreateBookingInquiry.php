<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Notifications\InquiryReceivedVisitorNotification;
use Modules\Bookings\Notifications\NewInquiryOwnerNotification;
use Modules\Listings\Models\Listing;

final class CreateBookingInquiry
{
    public function __construct(
        private readonly RecordBookingActivity $activity,
        private readonly RecordCustomerActivity $customerActivity,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Listing $listing, array $data, ?string $ipAddress): BookingInquiry
    {
        $inquiry = DB::transaction(function () use ($listing, $data, $ipAddress): BookingInquiry {
            $customer = Customer::query()
                ->where('team_id', $listing->team_id)
                ->where('email', (string) $data['email'])
                ->first();

            if (! $customer) {
                $customer = new Customer([
                    'name' => (string) $data['name'],
                    'email' => (string) $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'whatsapp_phone' => $data['whatsapp_phone'] ?? null,
                ]);
                $customer->team_id = $listing->team_id;
                $customer->save();
            } elseif (blank($customer->phone) && filled($data['phone'] ?? null)) {
                $customer->update(['phone' => $data['phone']]);
            }

            $inquiry = new BookingInquiry($data);
            $inquiry->team_id = $listing->team_id;
            $inquiry->listing_id = $listing->id;
            $inquiry->customer_id = $customer->id;
            $inquiry->status = InquiryStatus::New;
            $inquiry->source = 'public_web';
            $inquiry->consent_at = now();
            $inquiry->ip_hash = $ipAddress
                ? hash_hmac('sha256', $ipAddress, (string) config('app.key'))
                : null;
            $inquiry->save();

            return $inquiry;
        });

        $inquiry->loadMissing(['listing.team', 'listing.media']);

        $this->activity->handle($inquiry, 'booking.created', null, [
            'source' => 'public_web',
            'start_date' => $inquiry->start_date->toDateString(),
            'end_date' => $inquiry->end_date->toDateString(),
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

        Notification::route('mail', $inquiry->email)
            ->notify(new InquiryReceivedVisitorNotification($inquiry));

        $owner = $inquiry->listing->team?->owner();

        if ($owner instanceof User && $owner->wantsInquiryEmails()) {
            $owner->notify(new NewInquiryOwnerNotification($inquiry));
        }

        return $inquiry;
    }
}
