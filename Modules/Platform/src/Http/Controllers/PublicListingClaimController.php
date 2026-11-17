<?php

namespace Modules\Platform\Http\Controllers;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Branding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Listings\Models\Listing;
use Modules\Platform\Actions\SubmitListingClaim;
use Modules\Platform\Enums\ListingClaimRole;
use Modules\Platform\Http\Requests\StoreListingClaimRequest;
use Modules\Platform\Models\ListingClaim;
use Modules\Platform\Notifications\ListingClaimSubmitted;
use Modules\Platform\Support\PlatformAccess;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Throwable;

class PublicListingClaimController extends Controller
{
    public function create(Request $request, string $listing): Response|RedirectResponse
    {
        $model = $this->findListing($listing);

        if (! $model->isClaimable()) {
            Inertia::flash('toast', [
                'type' => 'info',
                'message' => 'Esta terraza ya fue reclamada por su propietario.',
            ]);

            return to_route('public.listings.show', ['listing' => $model->slug]);
        }

        $user = $request->user();
        $pending = null;

        if ($user === null) {
            $request->session()->put(
                'url.intended',
                route('public.claims.create', ['listing' => $model->slug]),
            );
        }

        if ($user !== null) {
            $pending = ListingClaim::query()
                ->where('listing_id', $model->id)
                ->where('user_id', $user->id)
                ->pending()
                ->first();
        }

        return Inertia::render('Platform/Public/Claim', [
            'listing' => [
                'name' => $model->name,
                'slug' => $model->slug,
                'city' => $model->city,
                'state' => $model->state,
                'address' => $model->address,
                'capacity' => $model->capacity,
                'cover_url' => $model->coverImage?->url,
            ],
            'roles' => ListingClaimRole::options(),
            'authenticated' => $user !== null,
            'verified' => $user?->hasVerifiedEmail() ?? false,
            'pendingClaim' => $pending === null ? null : [
                'status' => $pending->status->value,
                'status_label' => $pending->status->label(),
                'created_at' => $pending->created_at?->toIso8601String(),
            ],
            'targetTeams' => $user === null ? [] : $this->targetTeams($user),
            'urls' => [
                'store' => route('public.claims.store', ['listing' => $model->slug]),
                'listing' => route('public.listings.show', ['listing' => $model->slug]),
                'login' => route('login', absolute: false),
                'register' => route('register', absolute: false),
                'verify' => route('verification.notice'),
            ],
        ]);
    }

    public function store(
        StoreListingClaimRequest $request,
        string $listing,
        SubmitListingClaim $submitClaim,
    ): RedirectResponse {
        $model = $this->findListing($listing);
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        try {
            $claim = $submitClaim->handle(
                $model,
                $user,
                $request->validated(),
                $request->file('evidence'),
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'listing' => $exception->getMessage(),
            ]);
        }

        $this->notifyPlatform($claim);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Recibimos tu reclamación. Revisaremos la información y te contactaremos.',
        ]);

        return to_route('public.listings.show', ['listing' => $model->slug]);
    }

    /**
     * @return array<int, array{id: int, name: string, is_personal: bool}>
     */
    private function targetTeams(User $user): array
    {
        return $user->teams()
            ->where('teams.is_directory', false)
            ->wherePivotIn('role', [TeamRole::Owner->value, TeamRole::Admin->value])
            ->orderBy('teams.name')
            ->get(['teams.id', 'teams.name', 'teams.is_personal'])
            ->map(fn ($team): array => [
                'id' => $team->id,
                'name' => $team->name,
                'is_personal' => (bool) $team->is_personal,
            ])
            ->values()
            ->all();
    }

    private function notifyPlatform(ListingClaim $claim): void
    {
        try {
            $support = Branding::supportEmail() ?: Branding::fromAddress();

            if (filled($support)) {
                Notification::route('mail', $support)
                    ->notify(new ListingClaimSubmitted($claim));
            }

            $admins = Role::query()
                ->where('name', PlatformAccess::ROLE)
                ->where('guard_name', 'web')
                ->exists()
                ? User::role(PlatformAccess::ROLE)->get()
                : collect();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new ListingClaimSubmitted($claim));
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function findListing(string $slug): Listing
    {
        $listing = Listing::query()
            ->with(['coverImage', 'team'])
            ->where('slug', $slug)
            ->firstOrFail();

        abort_unless($listing->isPubliclyVisible(), 404);

        return $listing;
    }
}
