<?php

namespace Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Listings\Actions\RestoreListing;
use Modules\Listings\Actions\SuspendListing;
use Modules\Listings\Enums\ListingStatus;
use Modules\Listings\Models\Listing;
use Modules\Platform\Http\Requests\ModerationReasonRequest;
use Modules\Platform\Http\Requests\PlatformListingIndexRequest;
use Modules\Platform\Models\ListingReport;
use Modules\Platform\Support\ActivityPresenter;
use Spatie\Activitylog\Models\Activity;

class PlatformListingController extends Controller
{
    public function index(PlatformListingIndexRequest $request): Response
    {
        $search = trim((string) $request->validated('q', ''));
        $status = $request->validated('status');

        $listings = Listing::query()
            ->with(['team', 'coverImage'])
            ->withCount([
                'media as images_count' => fn ($query) => $query->where('type', 'image'),
                'media as videos_count' => fn ($query) => $query->where('type', 'video'),
            ])
            ->when(is_string($status) && $status !== '', fn ($query) => $query
                ->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('city', 'like', '%'.$search.'%')
                        ->orWhereHas('team', fn ($team) => $team
                            ->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $listings->through(fn (Listing $listing): array => [
            'id' => $listing->id,
            'name' => $listing->name,
            'slug' => $listing->slug,
            'status' => $listing->status->value,
            'status_label' => $listing->status->label(),
            'city' => $listing->city,
            'team_name' => $listing->team?->name,
            'team_slug' => $listing->team?->slug,
            'images_count' => (int) $listing->images_count,
            'videos_count' => (int) $listing->videos_count,
            'cover_url' => $listing->coverImage?->url,
            'is_suspended' => $listing->isSuspendedByPlatform(),
            'suspended_at' => $listing->suspended_at?->toDateString(),
            'created_at' => $listing->created_at?->toDateString(),
        ]);

        return Inertia::render('Platform/Listings/Index', [
            'listings' => $listings,
            'filters' => [
                'q' => $search,
                'status' => is_string($status) ? $status : null,
            ],
            'statuses' => array_map(
                fn (ListingStatus $case): array => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ],
                ListingStatus::cases(),
            ),
            'urls' => [
                'index' => route('platform.listings.index'),
                'dashboard' => route('platform.dashboard'),
            ],
        ]);
    }

    public function show(string $listing): Response
    {
        $model = $this->findListing($listing);

        $model->load(['team', 'media']);

        $reports = ListingReport::query()
            ->with('handledBy')
            ->where('listing_id', $model->id)
            ->latest()
            ->get();

        $activities = Activity::query()
            ->with('causer')
            ->where('log_name', 'moderation')
            ->where('subject_type', Listing::class)
            ->where('subject_id', $model->id)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (Activity $activity): array => ActivityPresenter::present($activity))
            ->all();

        return Inertia::render('Platform/Listings/Show', [
            'listing' => [
                'id' => $model->id,
                'name' => $model->name,
                'slug' => $model->slug,
                'description' => $model->description,
                'city' => $model->city,
                'state' => $model->state,
                'address' => $model->address,
                'capacity' => $model->capacity,
                'status' => $model->status->value,
                'status_label' => $model->status->label(),
                'is_suspended' => $model->isSuspendedByPlatform(),
                'suspended_at' => $model->suspended_at?->toIso8601String(),
                'suspension_reason' => $model->suspension_reason,
                'published_at' => $model->published_at?->toDateString(),
                'public_url' => $model->isPubliclyVisible()
                    ? route('public.listings.show', ['listing' => $model->slug])
                    : null,
                'team' => [
                    'id' => $model->team?->id,
                    'name' => $model->team?->name,
                    'slug' => $model->team?->slug,
                    'is_suspended' => $model->team?->isSuspended() ?? false,
                    'platform_url' => $model->team
                        ? route('platform.accounts.show', ['account' => $model->team->slug])
                        : null,
                ],
                'media' => $model->media->map(fn ($media): array => [
                    'id' => $media->id,
                    'type' => $media->type->value,
                    'url' => $media->url,
                ])->all(),
            ],
            'reports' => $reports->map(fn (ListingReport $report): array => [
                'id' => $report->id,
                'reason' => $report->reason->value,
                'reason_label' => $report->reason->label(),
                'status' => $report->status->value,
                'status_label' => $report->status->label(),
                'message' => $report->message,
                'created_at' => $report->created_at?->toIso8601String(),
            ])->all(),
            'activities' => $activities,
            'can' => [
                'moderate' => Gate::allows('platform.listings.moderate'),
            ],
            'urls' => [
                'index' => route('platform.listings.index'),
                'suspend' => route('platform.listings.suspend', ['listing' => $model->slug]),
                'restore' => route('platform.listings.restore', ['listing' => $model->slug]),
            ],
        ]);
    }

    public function suspend(
        ModerationReasonRequest $request,
        string $listing,
        SuspendListing $suspendListing,
    ): RedirectResponse {
        $model = $this->findListing($listing);

        $suspendListing->handle(
            $model,
            $request->user(),
            (string) $request->validated('reason'),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Terraza {$model->name} suspendida.",
        ]);

        return back();
    }

    public function restore(
        ModerationReasonRequest $request,
        string $listing,
        RestoreListing $restoreListing,
    ): RedirectResponse {
        $model = $this->findListing($listing);

        $restoreListing->handle(
            $model,
            $request->user(),
            (string) $request->validated('reason'),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Terraza {$model->name} restaurada.",
        ]);

        return back();
    }

    private function findListing(string $slug): Listing
    {
        return Listing::query()->where('slug', $slug)->firstOrFail();
    }
}
