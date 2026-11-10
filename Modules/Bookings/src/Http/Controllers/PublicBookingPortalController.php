<?php

namespace Modules\Bookings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Bookings\Actions\RecordCustomerActivity;
use Modules\Bookings\Enums\ContractStatus;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Http\Requests\AcceptBookingContractRequest;
use Modules\Bookings\Http\Requests\BookingFeedbackRequest;
use Modules\Bookings\Http\Requests\ConfirmBookingPortalRequest;
use Modules\Bookings\Models\BookingContract;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Notifications\BookingConfirmedByCustomer;
use Modules\Bookings\Notifications\BookingContractAccepted;
use Modules\Bookings\Notifications\BookingFeedbackReceived;

class PublicBookingPortalController extends Controller
{
    public function __construct(
        private readonly RecordCustomerActivity $activityLogger,
    ) {}

    public function show(string $token): Response
    {
        $booking = $this->findBooking($token);

        return Inertia::render('Bookings/Public/BookingPortal', [
            'booking' => $this->present($booking),
        ]);
    }

    public function contract(string $token): Response
    {
        $booking = $this->findBooking($token);
        $contract = $booking->contract;

        abort_if($contract === null || $contract->status->value === 'draft', 404);

        return Inertia::render('Bookings/Public/Contract', [
            'booking' => $this->present($booking),
            'contract' => $this->contractData($booking, $contract),
        ]);
    }

    public function acceptContract(
        AcceptBookingContractRequest $request,
        string $token,
    ): RedirectResponse {
        $booking = $this->findBooking($token);
        $contract = $booking->contract;

        if ($contract === null || ! $contract->canBeAccepted()) {
            return back()->withErrors([
                'contract' => 'El contrato ya no está disponible para aceptar.',
            ]);
        }

        $data = $request->validated();

        $contract->forceFill([
            'status' => ContractStatus::Accepted,
            'accepted_at' => now(),
            'accepted_by_name' => $data['name'],
            'accepted_ip_hash' => $request->ip()
                ? hash_hmac('sha256', $request->ip(), (string) config('app.key'))
                : null,
        ])->save();

        if ($booking->customer) {
            $this->activityLogger->handle(
                $booking->customer,
                CustomerActivityType::System,
                'El cliente aceptó el contrato de la reserva.',
                null,
                null,
                [
                    'booking_inquiry_id' => $booking->id,
                    'booking_contract_id' => $contract->id,
                ],
            );
        }

        $owner = $booking->listing->team?->owner();

        if ($owner !== null) {
            $owner->notify(new BookingContractAccepted($contract));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Contrato aceptado.',
        ]);

        return back();
    }

    public function confirm(
        ConfirmBookingPortalRequest $request,
        string $token,
    ): RedirectResponse {
        $booking = $this->findBooking($token);

        if (! $booking->canBeConfirmedByCustomer()) {
            return back()->withErrors([
                'booking' => 'Esta reserva ya no se puede confirmar.',
            ]);
        }

        $data = $request->validated();

        DB::transaction(function () use ($booking, $data): void {
            $booking->forceFill([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'whatsapp_phone' => $data['whatsapp_phone'] ?? null,
                'guests' => $data['guests'] ?? $booking->guests,
                'customer_confirmed_at' => now(),
            ])->save();

            $booking->customer?->update([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'whatsapp_phone' => $data['whatsapp_phone'] ?? null,
            ]);
        });

        if ($booking->customer) {
            $this->activityLogger->handle(
                $booking->customer,
                CustomerActivityType::System,
                'El cliente confirmó los datos de su reserva.',
                null,
                null,
                ['booking_inquiry_id' => $booking->id],
            );
        }

        $owner = $booking->listing->team?->owner();

        if ($owner !== null) {
            $owner->notify(new BookingConfirmedByCustomer($booking));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Datos confirmados. El anfitrión recibirá tu confirmación.',
        ]);

        return back();
    }

    public function feedback(
        BookingFeedbackRequest $request,
        string $token,
    ): RedirectResponse {
        $booking = $this->findBooking($token);

        if (! $booking->canReceiveFeedback()) {
            return back()->withErrors([
                'feedback' => 'La opinión ya no está disponible para esta reserva.',
            ]);
        }

        $data = $request->validated();

        $booking->forceFill([
            'feedback_rating' => $data['rating'],
            'feedback_comment' => $data['comment'] ?? null,
            'feedback_at' => now(),
        ])->save();

        if ($booking->customer) {
            $this->activityLogger->handle(
                $booking->customer,
                CustomerActivityType::System,
                "El cliente dejó una opinión: {$data['rating']}/5.",
                null,
                null,
                [
                    'booking_inquiry_id' => $booking->id,
                    'rating' => $data['rating'],
                ],
            );
        }

        $owner = $booking->listing->team?->owner();

        if ($owner !== null) {
            $owner->notify(new BookingFeedbackReceived($booking));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Gracias por tu opinión.',
        ]);

        return back();
    }

    public function calendar(string $token): \Illuminate\Http\Response
    {
        $booking = $this->findBooking($token);
        $end = $booking->end_date->copy()->addDay();

        $description = implode('\\n', array_filter([
            "Reserva #{$booking->id}",
            "Cliente: {$booking->name}",
            "Invitados: {$booking->guests}",
            $booking->event_type ? "Evento: {$booking->event_type}" : null,
        ]));

        $content = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//'.config('app.name').'//Reservas//ES',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:'.'booking-'.$booking->id.'@'.parse_url((string) config('app.url'), PHP_URL_HOST),
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART;VALUE=DATE:'.$booking->start_date->format('Ymd'),
            'DTEND;VALUE=DATE:'.$end->format('Ymd'),
            'SUMMARY:'.$this->escape("Reserva en {$booking->listing->name}"),
            'LOCATION:'.$this->escape($this->location($booking)),
            'DESCRIPTION:'.$this->escape($description),
            'END:VEVENT',
            'END:VCALENDAR',
        ]);

        return response($content, 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reserva-'.$booking->id.'.ics"',
        ]);
    }

    private function findBooking(string $token): BookingInquiry
    {
        return BookingInquiry::query()
            ->with(['listing.team', 'customer', 'contract'])
            ->where('portal_token', $token)
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(BookingInquiry $booking): array
    {
        return [
            'id' => $booking->id,
            'status' => $booking->status->value,
            'status_label' => $booking->status->label(),
            'listing' => [
                'name' => $booking->listing->name,
                'location' => $this->location($booking),
                'url' => route('public.listings.show', ['listing' => $booking->listing->slug]),
            ],
            'customer' => [
                'name' => $booking->name,
                'email' => $booking->email,
                'phone' => $booking->phone,
                'whatsapp_phone' => $booking->whatsapp_phone,
            ],
            'start_date' => $booking->start_date->toDateString(),
            'end_date' => $booking->end_date->toDateString(),
            'start_date_label' => $booking->start_date->format('d/m/Y'),
            'end_date_label' => $booking->end_date->format('d/m/Y'),
            'event_type' => $booking->event_type,
            'guests' => $booking->guests,
            'message' => $booking->message,
            'customer_confirmed_at' => $booking->customer_confirmed_at?->toIso8601String(),
            'feedback' => [
                'rating' => $booking->feedback_rating,
                'comment' => $booking->feedback_comment,
                'at' => $booking->feedback_at?->toIso8601String(),
            ],
            'can_confirm' => $booking->canBeConfirmedByCustomer(),
            'can_feedback' => $booking->canReceiveFeedback(),
            'contract' => $booking->contract
                ? $this->contractData($booking, $booking->contract)
                : null,
            'urls' => [
                'portal' => route('public.bookings.portal', ['token' => $booking->portal_token]),
                'confirm' => route('public.bookings.portal.confirm', ['token' => $booking->portal_token]),
                'feedback' => route('public.bookings.portal.feedback', ['token' => $booking->portal_token]),
                'calendar' => route('public.bookings.portal.calendar', ['token' => $booking->portal_token]),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function contractData(BookingInquiry $booking, BookingContract $contract): array
    {
        return [
            'id' => $contract->id,
            'title' => $contract->title,
            'body' => $contract->body,
            'status' => $contract->status->value,
            'status_label' => $contract->status->label(),
            'sent_at' => $contract->sent_at?->toIso8601String(),
            'accepted_at' => $contract->accepted_at?->toIso8601String(),
            'accepted_by_name' => $contract->accepted_by_name,
            'can_accept' => $contract->canBeAccepted(),
            'url' => route('public.bookings.portal.contract', ['token' => $booking->portal_token]),
            'accept_url' => route('public.bookings.portal.contract.accept', ['token' => $booking->portal_token]),
        ];
    }

    private function location(BookingInquiry $booking): string
    {
        return collect([
            $booking->listing->address,
            $booking->listing->city,
            $booking->listing->state,
        ])->filter()->implode(', ');
    }

    private function escape(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\n", "\r"],
            ['\\\\', '\\;', '\\,', '\\n', ''],
            $value,
        );
    }
}
