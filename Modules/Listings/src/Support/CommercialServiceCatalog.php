<?php

namespace Modules\Listings\Support;

final class CommercialServiceCatalog
{
    /**
     * Keys kept for existing services and packages that used the old mixed
     * characteristics catalog.
     *
     * @var array<string, string>
     */
    private const LEGACY_LABELS = [
        'lodging' => 'Hospedaje',
        'rooms' => 'Habitaciones',
    ];

    /**
     * @return array<int, array{key: string, label: string, services: array<int, array{value: string, label: string}>}>
     */
    public static function groups(): array
    {
        $configured = config('verticals.terrace.commercial_services', []);

        if (! is_array($configured)) {
            return [];
        }

        $groups = [];
        $legacy = [];

        foreach ($configured as $key => $group) {
            if (
                is_string($key)
                && is_array($group)
                && isset($group['items'])
                && is_array($group['items'])
            ) {
                $items = self::items($group['items']);

                if ($items === []) {
                    continue;
                }

                $groups[] = [
                    'key' => $key,
                    'label' => is_string($group['label'] ?? null)
                        ? $group['label']
                        : $key,
                    'services' => self::toOptions($items),
                ];

                continue;
            }

            if (is_string($key) && is_string($group)) {
                $legacy[$key] = $group;
            }
        }

        if ($legacy !== []) {
            $groups[] = [
                'key' => 'other',
                'label' => 'Otros servicios',
                'services' => self::toOptions($legacy),
            ];
        }

        return $groups;
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        $services = [];

        foreach (self::groups() as $group) {
            foreach ($group['services'] as $service) {
                $services[$service['value']] = $service['label'];
            }
        }

        foreach (self::LEGACY_LABELS as $key => $label) {
            $services[$key] ??= $label;
        }

        return $services;
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function label(string $key): string
    {
        return self::all()[$key] ?? $key;
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @return array<string, string>
     */
    private static function items(array $items): array
    {
        $services = [];

        foreach ($items as $key => $label) {
            if (is_string($key) && is_string($label)) {
                $services[$key] = $label;
            }
        }

        return $services;
    }

    /**
     * @param  array<string, string>  $services
     * @return array<int, array{value: string, label: string}>
     */
    private static function toOptions(array $services): array
    {
        $options = [];

        foreach ($services as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $options;
    }
}
