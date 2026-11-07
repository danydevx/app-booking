# Module structure

Business capabilities are explicit modules under `Modules/{Name}/`. This is a
modular monolith, not a plugin system and not a second package registry.

## Canonical layout

```text
Modules/
└── Listings/
    ├── src/
    │   ├── ListingsServiceProvider.php
    │   ├── Actions/
    │   ├── Console/Commands/
    │   ├── Database/Factories/
    │   ├── Enums/
    │   ├── Http/
    │   │   ├── Controllers/
    │   │   ├── Middleware/
    │   │   └── Requests/
    │   ├── Models/
    │   ├── Policies/
    │   └── Support/
    ├── routes/
    │   └── web.php
    ├── database/
    │   └── migrations/
    ├── resources/
    │   ├── js/
    │   │   ├── pages/       # Inertia pages, module-owned
    │   │   ├── components/  # module-only Vue components
    │   │   └── types.ts
    │   └── views/           # namespaced Blade views, e.g. listings::mail...
    └── tests/
        └── Feature/
```

The module name is PascalCase and singular or plural according to the domain
language: `Platform`, `Listings`, `Availability`, `Bookings`, `Billing`,
`Communications`, `Clients`.

## Registration

Register each module provider explicitly in `bootstrap/providers.php`. Do not
scan or auto-install modules at runtime.

```php
use Modules\Listings\ListingsServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    PlatformServiceProvider::class,
    ListingsServiceProvider::class,
];
```

The provider owns module wiring:

```php
final class ListingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $modulePath = dirname(__DIR__);

        $this->loadMigrationsFrom($modulePath.'/database/migrations');
        $this->loadViewsFrom($modulePath.'/resources/views', 'listings');
        $this->loadRoutesFrom($modulePath.'/routes/web.php');
    }
}
```

Module route files must include the `web` middleware because `loadRoutesFrom()`
only requires the file:

```php
Route::middleware(['web', 'auth', 'verified'])
    ->prefix('listings')
    ->name('listings.')
    ->group(function (): void {
        // ...
    });
```

## Namespaces and Composer

Each module has explicit PSR-4 entries in `composer.json`:

```json
"autoload": {
    "psr-4": {
        "Modules\\Listings\\": "Modules/Listings/src/"
    }
},
"autoload-dev": {
    "psr-4": {
        "Modules\\Listings\\Tests\\": "Modules/Listings/tests/"
    }
}
```

Run `composer dump-autoload` after adding a module.

## Inertia pages

A module page is addressed as `{Module}/{RelativePage}`:

| Inertia render                            | File                                                  |
| ----------------------------------------- | ----------------------------------------------------- |
| `Inertia::render('Platform/Dashboard')`   | `Modules/Platform/resources/js/pages/Dashboard.vue`   |
| `Inertia::render('Listings/Index')`       | `Modules/Listings/resources/js/pages/Index.vue`       |
| `Inertia::render('Listings/Public/Show')` | `Modules/Listings/resources/js/pages/Public/Show.vue` |

`resources/js/app.ts` loads core pages from `resources/js/pages` and module pages
from `Modules/*/resources/js/pages`. Module pages use the normal `AppLayout`
unless the page defines its own layout options.

Server-rendered Vite page paths are resolved by
`App\Support\Inertia\PageAsset`. The Vite config also registers module pages as
build inputs so `@vite` and the production manifest work. Inertia's single-root
`ensure_pages_exist` check is disabled; `PageAssetTest` verifies the module/core
mapping instead.

## Ownership rules

- A module may import shared core UI from `@/components/ui` and shared helpers
  from `@/`.
- Shared code must not import a module-specific page or component directly.
- Cross-module integration must go through a small interface, event or explicit
  service, not by reaching into another module's internal files.
- A tenant-owned module table still carries `team_id`; module boundaries do not
  weaken account isolation.
- Keep module migrations in the module, but never edit migrations that have
  already run outside local development.
- Module tests live in the module and are auto-discovered by `phpunit.xml`
  (`<directory>Modules</directory>`).

## What not to do

- No runtime ZIP installer.
- No plugin marketplace.
- No private Composer registry for first-party modules.
- No automatic module discovery that hides wiring.
- No module-specific copy of shadcn-vue components.
