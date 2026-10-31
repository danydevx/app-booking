<?php

namespace Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Listings\Models\Listing;
use Modules\Platform\Enums\ReportReason;
use Modules\Platform\Enums\ReportStatus;
use Modules\Platform\Http\Requests\StoreListingReportRequest;
use Modules\Platform\Models\ListingReport;

class PublicListingReportController extends Controller
{
    public function create(string $listing): Response
    {
        $model = $this->findListing($listing);

        return Inertia::render('Platform/Public/Report', [
            'listing' => [
                'name' => $model->name,
                'slug' => $model->slug,
                'city' => $model->city,
                'state' => $model->state,
                'cover_url' => $model->coverImage?->url,
            ],
            'reasons' => ReportReason::options(),
            'urls' => [
                'store' => route('public.reports.store', ['listing' => $model->slug]),
                'listing' => route('public.listings.show', ['listing' => $model->slug]),
                'index' => route('public.listings.index'),
            ],
        ]);
    }

    public function store(StoreListingReportRequest $request, string $listing): RedirectResponse
    {
        $model = $this->findListing($listing);

        ListingReport::query()->create([
            'listing_id' => $model->id,
            'team_id' => $model->team_id,
            'reporter_email' => $request->validated('reporter_email'),
            'reason' => $request->validated('reason'),
            'message' => $request->validated('message'),
            'status' => ReportStatus::Open,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Recibimos tu reporte. El equipo de plataforma lo revisará.',
        ]);

        return to_route('public.listings.show', ['listing' => $model->slug]);
    }

    private function findListing(string $slug): Listing
    {
        $listing = Listing::query()
            ->with('coverImage')
            ->where('slug', $slug)
            ->firstOrFail();

        abort_unless($listing->isPubliclyVisible(), 404);

        return $listing;
    }
}
