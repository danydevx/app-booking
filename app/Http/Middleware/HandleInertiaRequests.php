<?php

namespace App\Http\Middleware;

use App\Models\Team;
use App\Support\Branding;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Modules\Listings\Models\Listing;
use Modules\Platform\Support\PlatformAccess;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'notifications' => fn (): array => [
                'unread_count' => $user?->unreadNotifications()->count() ?? 0,
            ],
            'listingAdmin' => fn (): ?array => $this->listingAdmin($request),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'currentTeam' => fn () => $user?->currentTeam ? $user->toUserTeam($user->currentTeam) : null,
            'teams' => fn () => $user?->toUserTeams(includeCurrent: true) ?? [],
            'platform' => [
                'isSuperadmin' => fn (): bool => $user ? PlatformAccess::isSuperadmin($user) : false,
            ],
            'branding' => fn (): array => [
                'app_name' => Branding::appName(),
                'logo_url' => Branding::logoUrl(),
                'support_email' => Branding::supportEmail(),
                'legal' => Branding::legalLinks(),
            ],
        ];
    }

    /**
     * Shared submenu for every page inside a listing admin.
     *
     * Customer workspace routes keep their own customer submenu.
     *
     * @return array{submenu: array<int, array{title: string, href: string}>}|null
     */
    private function listingAdmin(Request $request): ?array
    {
        $route = $request->route();

        if ($route === null || $route->hasParameter('customer')) {
            return null;
        }

        $teamSlug = $route->parameter('current_team');
        $listing = $route->parameter('listing');

        if (! is_string($teamSlug)) {
            return null;
        }

        if (! $listing instanceof Listing) {
            if (! is_string($listing)) {
                return null;
            }

            $team = Team::query()->where('slug', $teamSlug)->first();

            if ($team === null) {
                return null;
            }

            $listing = Listing::query()
                ->where('team_id', $team->id)
                ->where('slug', $listing)
                ->first();

            if ($listing === null) {
                return null;
            }
        }

        return [
            'submenu' => [
                [
                    'title' => 'Resumen',
                    'href' => route('listings.manage', ['current_team' => $teamSlug, 'listing' => $listing->slug]),
                ],
                [
                    'title' => 'Reservaciones',
                    'href' => route('bookings.listing', ['current_team' => $teamSlug, 'listing' => $listing->slug]),
                ],
                [
                    'title' => 'Clientes',
                    'href' => route('bookings.listing.customers', ['current_team' => $teamSlug, 'listing' => $listing->slug]),
                ],
                [
                    'title' => 'Precios',
                    'href' => route('listings.pricing.edit', ['current_team' => $teamSlug, 'listing' => $listing->slug]),
                ],
                [
                    'title' => 'Paquetes',
                    'href' => route('listings.packages.index', ['current_team' => $teamSlug, 'listing' => $listing->slug]),
                ],
                [
                    'title' => 'Servicios',
                    'href' => route('listings.services.index', ['current_team' => $teamSlug, 'listing' => $listing->slug]),
                ],
                [
                    'title' => 'Promociones',
                    'href' => route('listings.promotions.index', ['current_team' => $teamSlug, 'listing' => $listing->slug]),
                ],
                [
                    'title' => 'Disponibilidad',
                    'href' => route('bookings.availability.edit', ['current_team' => $teamSlug, 'listing' => $listing->slug]),
                ],
                [
                    'title' => 'Fotos y datos',
                    'href' => route('listings.edit', ['current_team' => $teamSlug, 'listing' => $listing->slug]),
                ],
            ],
        ];
    }
}
