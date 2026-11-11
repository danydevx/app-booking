<?php

namespace Modules\Bookings\Http\Controllers;

use App\Concerns\ResolvesCurrentTeam;
use App\Http\Controllers\Controller;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Support\Entitlements;
use Modules\Bookings\Enums\CustomerPipelineStage;
use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Enums\QuoteStatus;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerQuote;
use Throwable;

class CrmReportController extends Controller
{
    use ResolvesCurrentTeam;

    public function __construct(
        private readonly Entitlements $entitlements,
    ) {}

    public function __invoke(Request $request): Response|RedirectResponse
    {
        $team = $this->currentTeam($request);

        Gate::authorize('viewAny', [BookingInquiry::class, $team]);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $timezone = $team->timezone;
        $from = $this->date($request->string('from')->toString(), now($timezone)->subDays(90), $timezone);
        $to = $this->date($request->string('to')->toString(), now($timezone), $timezone);

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        $fromStart = $from->startOfDay()->utc();
        $toEnd = $to->endOfDay()->utc();

        $inquiryQuery = BookingInquiry::query()
            ->where('team_id', $team->id)
            ->whereBetween('created_at', [$fromStart, $toEnd]);

        $statusCounts = (clone $inquiryQuery)
            ->get(['status'])
            ->countBy(fn (BookingInquiry $inquiry): string => $inquiry->status->value);

        $inquiries = (clone $inquiryQuery)->get(['created_at', 'contacted_at', 'accepted_at']);

        $responseHours = $inquiries
            ->filter(fn (BookingInquiry $inquiry): bool => $inquiry->contacted_at !== null)
            ->map(fn (BookingInquiry $inquiry): float => abs($inquiry->created_at->diffInMinutes($inquiry->contacted_at)) / 60);

        $acceptHours = $inquiries
            ->filter(fn (BookingInquiry $inquiry): bool => $inquiry->accepted_at !== null)
            ->map(fn (BookingInquiry $inquiry): float => abs($inquiry->created_at->diffInMinutes($inquiry->accepted_at)) / 60);

        $totalInquiries = $inquiries->count();
        $accepted = (int) ($statusCounts[InquiryStatus::Accepted->value] ?? 0);
        $declined = (int) ($statusCounts[InquiryStatus::Declined->value] ?? 0);
        $expired = (int) ($statusCounts[InquiryStatus::Expired->value] ?? 0);
        $contacted = (int) ($statusCounts[InquiryStatus::Contacted->value] ?? 0);

        $pipeline = Customer::query()
            ->where('team_id', $team->id)
            ->whereNull('archived_at')
            ->get(['pipeline_stage'])
            ->countBy(fn (Customer $customer): string => $customer->pipeline_stage->value);

        $sourceRows = (clone $inquiryQuery)
            ->get(['source', 'source_detail', 'utm_source', 'status'])
            ->groupBy(fn (BookingInquiry $inquiry): string => $this->sourceLabel($inquiry))
            ->map(function (Collection $group, string $source): array {
                $count = $group->count();
                $acceptedCount = $group
                    ->where('status', InquiryStatus::Accepted)
                    ->count();

                return [
                    'source' => $source,
                    'count' => $count,
                    'accepted' => $acceptedCount,
                    'conversion_percent' => $count > 0
                        ? round(($acceptedCount / $count) * 100, 1)
                        : 0.0,
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();

        $listingRows = (clone $inquiryQuery)
            ->with('listing:id,name')
            ->get(['id', 'listing_id', 'status'])
            ->groupBy('listing_id')
            ->map(function (Collection $group): array {
                $listing = $group->first()->listing;
                $count = $group->count();
                $acceptedCount = $group
                    ->where('status', InquiryStatus::Accepted)
                    ->count();

                return [
                    'listing' => $listing->name,
                    'count' => $count,
                    'accepted' => $acceptedCount,
                    'conversion_percent' => $count > 0
                        ? round(($acceptedCount / $count) * 100, 1)
                        : 0.0,
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();

        $monthlyStart = now($timezone)->startOfMonth()->subMonths(5);
        $monthly = BookingInquiry::query()
            ->where('team_id', $team->id)
            ->where('created_at', '>=', $monthlyStart)
            ->get(['created_at', 'status'])
            ->groupBy(fn (BookingInquiry $inquiry): string => $inquiry->created_at->timezone($timezone)->format('Y-m'))
            ->map(function (Collection $group, string $month): array {
                return [
                    'month' => $month,
                    'label' => CarbonImmutable::parse($month.'-01')->translatedFormat('M Y'),
                    'inquiries' => $group->count(),
                    'accepted' => $group->where('status', InquiryStatus::Accepted)->count(),
                ];
            })
            ->sortKeys()
            ->values()
            ->all();

        $quotes = CustomerQuote::query()
            ->where('team_id', $team->id)
            ->whereBetween('created_at', [$fromStart, $toEnd]);
        $quoteTotal = (clone $quotes)->count();
        $quoteAccepted = (clone $quotes)->where('status', QuoteStatus::Accepted)->count();

        $feedback = BookingInquiry::query()
            ->where('team_id', $team->id)
            ->whereNotNull('feedback_rating')
            ->get(['feedback_rating']);
        $feedbackCount = $feedback->count();
        $avgFeedback = $feedbackCount > 0
            ? round((float) $feedback->avg('feedback_rating'), 1)
            : null;

        return Inertia::render('Bookings/Reports/Index', [
            'team' => [
                'name' => $team->name,
                'slug' => $team->slug,
            ],
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'overview' => [
                'inquiries' => $totalInquiries,
                'contacted' => $contacted,
                'accepted' => $accepted,
                'declined' => $declined,
                'expired' => $expired,
                'conversion_percent' => $totalInquiries > 0
                    ? round(($accepted / $totalInquiries) * 100, 1)
                    : 0.0,
                'avg_first_response_hours' => $responseHours->isEmpty()
                    ? null
                    : round($responseHours->avg(), 1),
                'avg_accept_hours' => $acceptHours->isEmpty()
                    ? null
                    : round($acceptHours->avg(), 1),
                'customers' => Customer::query()
                    ->where('team_id', $team->id)
                    ->whereNull('archived_at')
                    ->count(),
                'quotes' => $quoteTotal,
                'quotes_accepted' => $quoteAccepted,
                'quote_conversion_percent' => $quoteTotal > 0
                    ? round(($quoteAccepted / $quoteTotal) * 100, 1)
                    : 0.0,
                'feedback_count' => $feedbackCount,
                'avg_feedback_rating' => $avgFeedback,
            ],
            'pipeline' => collect(CustomerPipelineStage::options())
                ->map(fn (array $stage): array => [
                    ...$stage,
                    'count' => (int) ($pipeline[$stage['value']] ?? 0),
                ])
                ->values()
                ->all(),
            'sources' => $sourceRows,
            'listings' => $listingRows,
            'monthly' => $monthly,
            'urls' => [
                'reports' => route('bookings.reports', ['current_team' => $team->slug]),
                'bookings' => route('bookings.index', ['current_team' => $team->slug]),
                'customers' => route('bookings.customers.index', ['current_team' => $team->slug]),
                'quotes' => route('bookings.quotes.index', ['current_team' => $team->slug]),
            ],
        ]);
    }

    private function date(string $value, CarbonImmutable $default, string $timezone): CarbonImmutable
    {
        if ($value === '') {
            return $default;
        }

        try {
            return CarbonImmutable::parse($value, $timezone);
        } catch (Throwable) {
            return $default;
        }
    }

    private function sourceLabel(BookingInquiry $inquiry): string
    {
        return $inquiry->utm_source
            ?: $inquiry->source_detail
            ?: $inquiry->source;
    }

    private function upgradeRedirect(Team $team): RedirectResponse
    {
        Inertia::flash('toast', [
            'type' => 'error',
            'message' => 'Reportes está disponible en el plan Pro.',
        ]);

        return to_route('billing.index', ['current_team' => $team->slug]);
    }
}
