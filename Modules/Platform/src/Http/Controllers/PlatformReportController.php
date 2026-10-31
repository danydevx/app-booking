<?php

namespace Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Platform\Actions\HandleReport;
use Modules\Platform\Enums\ReportResolution;
use Modules\Platform\Enums\ReportStatus;
use Modules\Platform\Http\Requests\PlatformReportIndexRequest;
use Modules\Platform\Http\Requests\UpdateListingReportRequest;
use Modules\Platform\Models\ListingReport;

class PlatformReportController extends Controller
{
    public function index(PlatformReportIndexRequest $request): Response
    {
        $status = $request->validated('status');

        $reports = ListingReport::query()
            ->with(['listing', 'team', 'handledBy'])
            ->when(is_string($status) && $status !== '', fn ($query) => $query
                ->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $reports->through(fn (ListingReport $report): array => [
            'id' => $report->id,
            'reason' => $report->reason->value,
            'reason_label' => $report->reason->label(),
            'status' => $report->status->value,
            'status_label' => $report->status->label(),
            'message' => $report->message,
            'reporter_email' => $report->reporter_email,
            'resolution_action' => $report->resolution_action?->value,
            'resolution_action_label' => $report->resolution_action?->label(),
            'resolution_note' => $report->resolution_note,
            'handled_by' => $report->handledBy?->name,
            'handled_at' => $report->handled_at?->toIso8601String(),
            'created_at' => $report->created_at?->toIso8601String(),
            'update_url' => route('platform.reports.update', ['report' => $report->id]),
            'listing' => [
                'id' => $report->listing->id,
                'name' => $report->listing->name,
                'slug' => $report->listing->slug,
                'status' => $report->listing->status->value,
                'url' => route('platform.listings.show', [
                    'listing' => $report->listing->slug,
                ]),
            ],
            'account' => [
                'name' => $report->team->name,
                'slug' => $report->team->slug,
                'url' => route('platform.accounts.show', [
                    'account' => $report->team->slug,
                ]),
            ],
        ]);

        return Inertia::render('Platform/Reports/Index', [
            'reports' => $reports,
            'filters' => ['status' => is_string($status) ? $status : null],
            'statuses' => array_map(
                fn (ReportStatus $case): array => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ],
                ReportStatus::cases(),
            ),
            'resolutions' => ReportResolution::options(),
            'urls' => [
                'index' => route('platform.reports.index'),
                'dashboard' => route('platform.dashboard'),
            ],
        ]);
    }

    public function update(
        UpdateListingReportRequest $request,
        ListingReport $report,
        HandleReport $handleReport,
    ): RedirectResponse {
        $validated = $request->validated();

        $handleReport->handle($report, $request->user(), [
            'status' => (string) $validated['status'],
            'resolution_action' => isset($validated['resolution_action']) && is_string($validated['resolution_action'])
                ? $validated['resolution_action']
                : null,
            'resolution_note' => isset($validated['resolution_note']) && is_string($validated['resolution_note'])
                ? $validated['resolution_note']
                : null,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Reporte actualizado.',
        ]);

        return back();
    }
}
