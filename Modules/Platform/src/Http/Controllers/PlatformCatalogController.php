<?php

namespace Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingCharacteristic;
use Modules\Listings\Models\ListingEventType;
use Modules\Listings\Support\TerraceServiceCatalog;
use Modules\Platform\Http\Requests\UpdatePlatformCatalogRequest;

class PlatformCatalogController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Platform/Catalogs/Index', [
            'eventTypes' => ListingEventType::query()
                ->withCount('listings')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (ListingEventType $eventType): array => [
                    'id' => $eventType->id,
                    'slug' => $eventType->slug,
                    'name' => $eventType->name,
                    'description' => $eventType->description,
                    'icon' => $eventType->icon,
                    'is_active' => $eventType->is_active,
                    'sort_order' => $eventType->sort_order,
                    'listings_count' => (int) $eventType->listings_count,
                ])
                ->values()
                ->all(),
            'characteristics' => ListingCharacteristic::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (ListingCharacteristic $characteristic): array => [
                    'id' => $characteristic->id,
                    'service_key' => $characteristic->service_key,
                    'name' => TerraceServiceCatalog::label($characteristic->service_key),
                    'description' => $characteristic->description,
                    'icon' => $characteristic->icon,
                    'is_featured' => $characteristic->is_featured,
                    'sort_order' => $characteristic->sort_order,
                    'listings_count' => Listing::query()
                        ->whereJsonContains('services', $characteristic->service_key)
                        ->count(),
                ])
                ->values()
                ->all(),
            'urls' => [
                'update' => route('platform.catalogs.update'),
                'dashboard' => route('platform.dashboard'),
            ],
        ]);
    }

    public function update(
        UpdatePlatformCatalogRequest $request,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $eventTypes = (array) $request->validated('event_types');
        $characteristics = (array) $request->validated('characteristics');

        DB::transaction(function () use ($eventTypes, $characteristics, $actor): void {
            foreach ($eventTypes as $payload) {
                $id = isset($payload['id']) ? (int) $payload['id'] : null;

                if ($id !== null) {
                    $eventType = ListingEventType::query()->findOrFail($id);
                    $eventType->update([
                        'name' => $payload['name'],
                        'description' => $payload['description'] ?? null,
                        'icon' => $payload['icon'] ?? null,
                        'is_active' => (bool) $payload['is_active'],
                        'sort_order' => (int) $payload['sort_order'],
                    ]);

                    continue;
                }

                ListingEventType::query()->create([
                    'slug' => $this->uniqueSlug((string) $payload['name']),
                    'name' => $payload['name'],
                    'description' => $payload['description'] ?? null,
                    'icon' => $payload['icon'] ?? null,
                    'is_active' => (bool) $payload['is_active'],
                    'sort_order' => (int) $payload['sort_order'],
                ]);
            }

            foreach ($characteristics as $payload) {
                $characteristic = ListingCharacteristic::query()
                    ->findOrFail((int) $payload['id']);

                $characteristic->update([
                    'description' => $payload['description'] ?? null,
                    'icon' => $payload['icon'] ?? null,
                    'is_featured' => (bool) $payload['is_featured'],
                    'sort_order' => (int) $payload['sort_order'],
                ]);
            }

            activity('moderation')
                ->causedBy($actor)
                ->withProperties([
                    'event_types' => count($eventTypes),
                    'characteristics' => count($characteristics),
                ])
                ->log('platform.catalogs.updated');
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Catálogos actualizados.',
        ]);

        return back();
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'evento';
        }

        $slug = $base;
        $suffix = 2;

        while (ListingEventType::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
