<?php

namespace Modules\Listings\Support;

use Modules\Listings\Models\ListingCharacteristic;
use Modules\Listings\Models\ListingEventType;

final class ListingPublicCatalog
{
    /**
     * Event types available for public selectors and owner forms.
     *
     * @return array<int, array{id: int, slug: string, name: string, description: string|null, icon: string|null}>
     */
    public static function activeEventTypes(): array
    {
        return ListingEventType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'slug', 'name', 'description', 'icon'])
            ->map(fn (ListingEventType $eventType): array => [
                'id' => $eventType->id,
                'slug' => $eventType->slug,
                'name' => $eventType->name,
                'description' => $eventType->description,
                'icon' => $eventType->icon,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string, description: string|null}>
     */
    public static function eventTypeOptions(): array
    {
        return collect(self::activeEventTypes())
            ->map(fn (array $eventType): array => [
                'value' => $eventType['slug'],
                'label' => $eventType['name'],
                'description' => $eventType['description'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function activeEventSlugs(): array
    {
        return ListingEventType::query()
            ->where('is_active', true)
            ->pluck('slug')
            ->all();
    }

    /**
     * Characteristics highlighted on the landing search and owner form.
     *
     * @return array<int, array{key: string, name: string, description: string|null, icon: string|null}>
     */
    public static function featuredCharacteristics(): array
    {
        $catalog = TerraceServiceCatalog::all();

        return ListingCharacteristic::query()
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (ListingCharacteristic $characteristic): bool => isset($catalog[$characteristic->service_key]))
            ->map(fn (ListingCharacteristic $characteristic): array => [
                'key' => $characteristic->service_key,
                'name' => $catalog[$characteristic->service_key],
                'description' => $characteristic->description,
                'icon' => $characteristic->icon,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function featuredKeys(): array
    {
        return array_column(self::featuredCharacteristics(), 'key');
    }
}
