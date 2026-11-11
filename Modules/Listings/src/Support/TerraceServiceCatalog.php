<?php

namespace Modules\Listings\Support;

final class TerraceServiceCatalog
{
    /**
     * @var array<int, string>
     */
    private const PRIORITY = [
        'pool',
        'heated_pool',
        'parking',
        'wifi',
        'grill',
        'kitchen',
        'bathroom',
        'tables',
        'bounce_house',
        'green_areas',
        'covered_terrace',
        'lodging',
        'accessible',
    ];

    /**
     * @return array<int, array{key: string, label: string, services: array<int, array{value: string, label: string}>}>
     */
    public static function groups(): array
    {
        $configured = config('verticals.terrace.services', []);

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
                'label' => 'Otros',
                'services' => self::toOptions($legacy),
            ];
        }

        return $groups;
    }

    /**
     * @return array<int, array{key: string, label: string, services: array<int, array{value: string, label: string}>}>
     */
    public static function commercialServiceGroups(): array
    {
        return array_values(array_filter(
            self::groups(),
            fn (array $group): bool => in_array($group['key'], ['lodging', 'extras'], true),
        ));
    }

    /**
     * @return array<int, string>
     */
    public static function commercialServiceKeys(): array
    {
        return self::keysFromGroups(self::commercialServiceGroups());
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

        return $services;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return self::toOptions(self::all());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function priorityOptions(): array
    {
        $all = self::all();
        $options = [];

        foreach (self::PRIORITY as $key) {
            if (isset($all[$key])) {
                $options[] = [
                    'value' => $key,
                    'label' => $all[$key],
                ];
            }
        }

        return $options;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function additionalOptions(): array
    {
        $priority = array_column(self::priorityOptions(), 'value');

        return array_values(array_filter(
            self::options(),
            fn (array $option): bool => ! in_array($option['value'], $priority, true),
        ));
    }

    /**
     * Return only the groups that contain selected services, without empty groups.
     *
     * @param  array<int, string>  $keys
     * @return array<int, array{key: string, label: string, services: array<int, array{value: string, label: string}>}>
     */
    public static function groupsFor(array $keys): array
    {
        $selected = [];

        foreach ($keys as $key) {
            if ($key !== '' && ! in_array($key, $selected, true)) {
                $selected[] = $key;
            }
        }

        if ($selected === []) {
            return [];
        }

        $groups = [];
        $used = [];

        foreach (self::groups() as $group) {
            $services = array_values(array_filter(
                $group['services'],
                fn (array $service): bool => in_array($service['value'], $selected, true),
            ));

            if ($services === []) {
                continue;
            }

            $groups[] = [
                'key' => $group['key'],
                'label' => $group['label'],
                'services' => $services,
            ];

            foreach ($services as $service) {
                $used[] = $service['value'];
            }
        }

        $unknown = array_values(array_diff($selected, $used));

        if ($unknown !== []) {
            $groups[] = [
                'key' => 'other',
                'label' => 'Otros',
                'services' => array_map(
                    fn (string $key): array => [
                        'value' => $key,
                        'label' => $key,
                    ],
                    $unknown,
                ),
            ];
        }

        return $groups;
    }

    public static function label(string $key): string
    {
        return self::all()[$key] ?? $key;
    }

    /**
     * @param  array<int, array{key: string, label: string, services: array<int, array{value: string, label: string}>}>  $groups
     * @return array<int, string>
     */
    private static function keysFromGroups(array $groups): array
    {
        $keys = [];

        foreach ($groups as $group) {
            foreach ($group['services'] as $service) {
                $keys[] = $service['value'];
            }
        }

        return $keys;
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
