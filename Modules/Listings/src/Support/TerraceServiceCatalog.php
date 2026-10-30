<?php

namespace Modules\Listings\Support;

final class TerraceServiceCatalog
{
    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        $configured = config('verticals.terrace.services', []);

        if (! is_array($configured)) {
            return [];
        }

        $services = [];

        foreach ($configured as $key => $label) {
            if (is_string($key) && is_string($label)) {
                $services[$key] = $label;
            }
        }

        return $services;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::all() as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $options;
    }

    public static function label(string $key): string
    {
        return self::all()[$key] ?? $key;
    }
}
