<?php

namespace App\Http\Controllers;

use App\Concerns\ResolvesCurrentTeam;
use App\Models\TeamInvitation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Bookings\Enums\DepositStatus;
use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Models\BookingDeposit;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Listings\Models\Listing;

class DashboardController extends Controller
{
    use ResolvesCurrentTeam;

    public function __invoke(Request $request): Response
    {
        $team = $this->currentTeam($request);
        $email = strtolower($request->user()->email);
        $today = now($team->timezone)->toDateString();

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        $upcomingBookings = BookingInquiry::query()
            ->with('listing')
            ->where('team_id', $team->id)
            ->where('status', InquiryStatus::Accepted)
            ->where('start_date', '>=', $today)
            ->orderBy('start_date')
            ->limit(5)
            ->get()
            ->map(fn (BookingInquiry $inquiry): array => [
                'id' => $inquiry->id,
                'name' => $inquiry->name,
                'listing' => $inquiry->listing->name,
                'start_date' => $inquiry->start_date->toDateString(),
                'end_date' => $inquiry->end_date->toDateString(),
                'show_url' => route('bookings.show', [
                    'current_team' => $team->slug,
                    'inquiry' => $inquiry->id,
                ]),
            ])
            ->all();

        $recentInquiries = BookingInquiry::query()
            ->with('listing')
            ->where('team_id', $team->id)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (BookingInquiry $inquiry): array => [
                'id' => $inquiry->id,
                'name' => $inquiry->name,
                'listing' => $inquiry->listing->name,
                'status' => $inquiry->status->value,
                'status_label' => $inquiry->status->label(),
                'created_at' => $inquiry->created_at?->toDateString(),
                'show_url' => route('bookings.show', [
                    'current_team' => $team->slug,
                    'inquiry' => $inquiry->id,
                ]),
            ])
            ->all();

        $depositsReceived = BookingDeposit::query()
            ->whereHas('inquiry', fn ($query) => $query->where('team_id', $team->id))
            ->where('status', DepositStatus::Received)
            ->sum('amount_cents');

        return Inertia::render('Dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'overview' => [
                'listings' => Listing::query()->where('team_id', $team->id)->count(),
                'published_listings' => Listing::query()
                    ->where('team_id', $team->id)
                    ->published()
                    ->count(),
                'new_inquiries' => BookingInquiry::query()
                    ->where('team_id', $team->id)
                    ->where('status', InquiryStatus::New)
                    ->count(),
                'upcoming_bookings' => BookingInquiry::query()
                    ->where('team_id', $team->id)
                    ->where('status', InquiryStatus::Accepted)
                    ->where('start_date', '>=', $today)
                    ->count(),
                'customers' => Customer::query()->where('team_id', $team->id)->count(),
                'deposits_received_cents' => (int) $depositsReceived,
                'currency' => (string) config('bookings.deposit_currency', 'MXN'),
            ],
            'upcomingBookings' => $upcomingBookings,
            'recentInquiries' => $recentInquiries,
            'urls' => [
                'listings' => route('listings.index', ['current_team' => $team->slug]),
                'bookings' => route('bookings.index', ['current_team' => $team->slug]),
                'customers' => route('bookings.customers.index', ['current_team' => $team->slug]),
            ],
        ]);
    }
}
