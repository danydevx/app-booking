<?php

namespace Modules\Listings\Support;

final class MexicoLocationCatalog
{
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
