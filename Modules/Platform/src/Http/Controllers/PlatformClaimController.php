<?php

namespace Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Platform\Actions\ApproveListingClaim;
use Modules\Platform\Actions\RejectListingClaim;
use Modules\Platform\Enums\ListingClaimStatus;
use Modules\Platform\Http\Requests\PlatformClaimIndexRequest;
use Modules\Platform\Http\Requests\RejectListingClaimRequest;
use Modules\Platform\Models\ListingClaim;
use Modules\Platform\Notifications\ListingClaimApproved;
use Modules\Platform\Notifications\ListingClaimRejected;
use Modules\Platform\Support\PlatformPermission;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PlatformClaimController extends Controller
{
    public function index(PlatformClaimIndexRequest $request): Response
    {
        $search = trim((string) $request->validated('q', ''));
        $status = $request->validated('status');

        $claims = ListingClaim::query()
            ->with(['listing.team', 'claimant', 'targetTeam', 'team'])
            ->when(
                is_string($status) && $status !== '',
                fn ($query) => $query->where('status', $status),
            )
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('business_name', 'like', '%'.$search.'%')
                        ->orWhere('claimant_name', 'like', '%'.$search.'%')
                        ->orWhere('claimant_email', 'like', '%'.$search.'%')
                        ->orWhere('claimant_phone', 'like', '%'.$search.'%')
                        ->orWhereHas('listing', fn ($listing) => $listing
                            ->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $claims->through(fn (ListingClaim $claim): array => [
            'id' => $claim->id,
            'status' => $claim->status->value,
            'status_label' => $claim->status->label(),
            'created_at' => $claim->created_at?->toIso8601String(),
            'business_name' => $claim->business_name,
            'claimant_name' => $claim->claimant_name,
            'claimant_email' => $claim->claimant_email,
            'claimant_phone' => $claim->claimant_phone,
            'role_label' => $claim->role->label(),
            'has_evidence' => $claim->hasEvidence(),
            'listing' => [
                'id' => $claim->listing->id,
                'name' => $claim->listing->name,
                'slug' => $claim->listing->slug,
                'city' => $claim->listing->city,
                'state' => $claim->listing->state,
                'is_claimable' => $claim->listing->isClaimable(),
            ],
            'target_team' => $claim->targetTeam === null ? null : [
                'id' => $claim->targetTeam->id,
                'name' => $claim->targetTeam->name,
            ],
            'approve_url' => $claim->isPending() && $claim->listing->isClaimable()
                ? route('platform.claims.approve', ['claim' => $claim->id])
                : null,
        ]);

        $counts = ListingClaim::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return Inertia::render('Platform/Claims/Index', [
            'claims' => $claims,
            'counts' => [
                'pending' => (int) ($counts[ListingClaimStatus::Pending->value] ?? 0),
                'approved' => (int) ($counts[ListingClaimStatus::Approved->value] ?? 0),
                'rejected' => (int) ($counts[ListingClaimStatus::Rejected->value] ?? 0),
            ],
            'filters' => [
                'q' => $search,
                'status' => is_string($status) ? $status : null,
            ],
            'statuses' => ListingClaimStatus::options(),
            'can' => [
                'manage' => Gate::allows(PlatformPermission::CLAIMS_MANAGE),
            ],
            'urls' => [
                'index' => route('platform.claims.index'),
                'dashboard' => route('platform.dashboard'),
            ],
        ]);
    }

    public function show(ListingClaim $claim): Response
    {
        $claim->load(['listing.team', 'claimant', 'targetTeam', 'reviewedBy']);

        return Inertia::render('Platform/Claims/Show', [
            'claim' => [
                'id' => $claim->id,
                'status' => $claim->status->value,
                'status_label' => $claim->status->label(),
                'business_name' => $claim->business_name,
                'role' => $claim->role->value,
                'role_label' => $claim->role->label(),
                'claimant_name' => $claim->claimant_name,
                'claimant_email' => $claim->claimant_email,
                'claimant_phone' => $claim->claimant_phone,
                'whatsapp_phone' => $claim->whatsapp_phone,
                'message' => $claim->message,
                'has_evidence' => $claim->hasEvidence(),
                'evidence_name' => $claim->evidence_name,
                'evidence_size' => $claim->evidence_size,
                'rejection_reason' => $claim->rejection_reason,
                'created_at' => $claim->created_at?->toIso8601String(),
                'reviewed_at' => $claim->reviewed_at?->toIso8601String(),
                'reviewed_by' => $claim->reviewedBy?->name,
                'listing' => [
                    'id' => $claim->listing->id,
                    'name' => $claim->listing->name,
                    'slug' => $claim->listing->slug,
                    'city' => $claim->listing->city,
                    'state' => $claim->listing->state,
                    'address' => $claim->listing->address,
                    'status' => $claim->listing->status->value,
                    'status_label' => $claim->listing->status->label(),
                    'is_claimable' => $claim->listing->isClaimable(),
                    'is_claimed' => $claim->listing->isClaimed(),
                    'public_url' => $claim->listing->isPublished()
                        ? route('public.listings.show', ['listing' => $claim->listing->slug])
                        : null,
                    'platform_url' => route('platform.listings.show', ['listing' => $claim->listing->slug]),
                    'team' => [
                        'id' => $claim->listing->team->id,
                        'name' => $claim->listing->team->name,
                        'is_directory' => $claim->listing->team->isDirectory(),
                    ],
                ],
                'target_team' => $claim->targetTeam === null ? null : [
                    'id' => $claim->targetTeam->id,
                    'name' => $claim->targetTeam->name,
                    'platform_url' => route('platform.accounts.show', ['account' => $claim->targetTeam->slug]),
                ],
                'claimant' => $claim->claimant === null ? null : [
                    'id' => $claim->claimant->id,
                    'name' => $claim->claimant->name,
                    'email' => $claim->claimant->email,
                    'platform_url' => route('platform.users.show', ['user' => $claim->claimant->id]),
                ],
            ],
            'can' => [
                'manage' => Gate::allows(PlatformPermission::CLAIMS_MANAGE),
            ],
            'urls' => [
                'index' => route('platform.claims.index'),
                'approve' => route('platform.claims.approve', ['claim' => $claim->id]),
                'reject' => route('platform.claims.reject', ['claim' => $claim->id]),
                'evidence' => $claim->hasEvidence()
                    ? route('platform.claims.evidence', ['claim' => $claim->id])
                    : null,
                'listing' => route('platform.listings.show', ['listing' => $claim->listing->slug]),            ],
        ]);
    }

    public function approve(
        Request $request,
        ListingClaim $claim,
        ApproveListingClaim $approve,
    ): RedirectResponse {
        $result = $approve->handle($claim, $request->user());

        try {
            $result['claim']->claimant?->notify(new ListingClaimApproved($result['claim']));

            foreach ($result['auto_rejected'] as $rejected) {
                $rejected->claimant?->notify(new ListingClaimRejected($rejected));
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Reclamación aprobada. La terraza ya pertenece al propietario.',
        ]);

        return to_route('platform.claims.show', ['claim' => $claim->id]);
    }

    public function reject(
        RejectListingClaimRequest $request,
        ListingClaim $claim,
        RejectListingClaim $reject,
    ): RedirectResponse {
        $rejected = $reject->handle(
            $claim,
            $request->user(),
            (string) $request->validated('reason'),
        );

        try {
            $rejected->claimant?->notify(new ListingClaimRejected($rejected));
        } catch (Throwable $exception) {
            report($exception);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Reclamación rechazada.',
        ]);

        return to_route('platform.claims.show', ['claim' => $claim->id]);
    }

    public function evidence(ListingClaim $claim): StreamedResponse
    {
        abort_unless($claim->hasEvidence(), 404);

        $disk = Storage::disk((string) config('listings.claims.evidence_disk', 'local'));

        abort_unless($disk->exists((string) $claim->evidence_path), 404);

        return $disk->download(
            (string) $claim->evidence_path,
            $claim->evidence_name ?: basename((string) $claim->evidence_path),
        );
    }
}
