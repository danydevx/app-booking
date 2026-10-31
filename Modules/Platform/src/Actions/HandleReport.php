<?php

namespace Modules\Platform\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Listings\Actions\SuspendListing;
use Modules\Platform\Enums\ReportResolution;
use Modules\Platform\Enums\ReportStatus;
use Modules\Platform\Models\ListingReport;

final class HandleReport
{
    public function __construct(
        private readonly SuspendListing $suspendListing,
    ) {}

    /**
     * @param  array{status: string, resolution_action?: string|null, resolution_note?: string|null}  $data
     */
    public function handle(ListingReport $report, User $actor, array $data): ListingReport
    {
        return DB::transaction(function () use ($report, $actor, $data): ListingReport {
            $report->refresh();

            $status = ReportStatus::from($data['status']);
            $resolutionValue = $data['resolution_action'] ?? null;
            $resolution = $resolutionValue === null
                ? null
                : ReportResolution::from($resolutionValue);
            $noteValue = $data['resolution_note'] ?? null;
            $note = $noteValue === null ? null : trim($noteValue);

            $report->forceFill([
                'status' => $status,
                'resolution_action' => $status->isFinal() ? $resolution : null,
                'resolution_note' => $status->isFinal() ? $note : null,
                'handled_by_id' => $status === ReportStatus::Reviewing ? null : $actor->id,
                'handled_at' => $status->isFinal() ? now() : null,
            ])->save();

            activity('moderation')
                ->performedOn($report)
                ->causedBy($actor)
                ->withProperties([
                    'status' => $status->value,
                    'resolution_action' => $resolution?->value,
                    'resolution_note' => $note,
                    'listing_id' => $report->listing_id,
                    'team_id' => $report->team_id,
                ])
                ->log('report.'.$status->value);

            if ($status === ReportStatus::Resolved && $resolution === ReportResolution::ListingSuspended) {
                $this->suspendListing->handle(
                    $report->listing,
                    $actor,
                    $note !== null && $note !== ''
                        ? $note
                        : 'Suspendida al resolver un reporte de la plataforma.',
                );
            }

            return $report->refresh();
        });
    }
}
