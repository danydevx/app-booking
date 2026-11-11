<?php

namespace Modules\Listings\Support;

final class MexicoLocationCatalog
{
    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function countries(): array
    {
        $configured = config('locations.countries', []);

        if (! is_array($configured)) {
            return [];
        }

        $countries = [];

        foreach ($configured as $value => $label) {
            if (
                is_string($value)
                && $value !== ''
                && is_string($label)
                && $label !== ''
            ) {
                $countries[] = [
                    'value' => $value,
                    'label' => $label,
                ];
            }
        }

        return $countries;
    }

    /**
     * @return array<int, string>
     */
    public static function countryValues(): array
    {
        return array_column(self::countries(), 'value');
    }

    /**
     * Return the states available for the given country.
     *
     * @return array<string, array{label: string, municipalities: array<int, string>}>
     */
    public static function statesFor(string $country): array
    {
        return in_array($country, self::countryValues(), true) ? self::all() : [];
    }

    /**
     * @return array<string, array{label: string, municipalities: array<int, string>}>
     */
    public static function all(): array
    {
        $configured = config('locations.states', []);

        if (! is_array($configured)) {
            return [];
        }

        $states = [];

        foreach ($configured as $key => $state) {
            if (
                ! is_string($key) ||
                ! is_array($state) ||
                ! isset($state['label']) ||
                ! is_string($state['label'])
            ) {
                continue;
            }

            $municipalities = [];

            foreach ((array) ($state['municipalities'] ?? []) as $municipality) {
                if (is_string($municipality) && trim($municipality) !== '') {
                    $municipalities[] = $municipality;
                }
            }

            sort($municipalities, SORT_NATURAL | SORT_FLAG_CASE);

            $states[$key] = [
                'label' => $state['label'],
                'municipalities' => array_values(array_unique($municipalities)),
            ];
        }

        return $states;
    }

    /**
     * @return array<int, string>
     */
    public static function stateValues(): array
    {
        return array_keys(self::all());
    }

    /**
     * @return array<int, string>
     */
    public static function municipalities(string $state): array
    {
        return self::all()[$state]['municipalities'] ?? [];
    }

    public static function label(string $state): string
    {
        return self::all()[$state]['label'] ?? $state;
    }
}
