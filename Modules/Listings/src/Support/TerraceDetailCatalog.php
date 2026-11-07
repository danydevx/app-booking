<?php

namespace Modules\Listings\Support;

final class TerraceDetailCatalog
{
    /**
     * @return array<string, array{label: string, type: string, options?: array<string, string>, placeholder?: string}>
     */
    public static function all(): array
    {
        $configured = config('verticals.terrace.details', []);

        if (! is_array($configured)) {
            return [];
        }

        $fields = [];

        foreach ($configured as $key => $field) {
            if (
                ! is_string($key) ||
                ! is_array($field) ||
                ! isset($field['label']) ||
                ! is_string($field['label'])
            ) {
                continue;
            }

            $normalized = [
                'label' => $field['label'],
                'type' => isset($field['type']) && is_string($field['type'])
                    ? $field['type']
                    : 'text',
            ];

            if (isset($field['placeholder']) && is_string($field['placeholder'])) {
                $normalized['placeholder'] = $field['placeholder'];
            }

            if (isset($field['options']) && is_array($field['options'])) {
                $options = [];

                foreach ($field['options'] as $value => $label) {
                    if (is_string($value) && is_string($label)) {
                        $options[$value] = $label;
                    }
                }

                $normalized['options'] = $options;
            }

            $fields[$key] = $normalized;
        }

        return $fields;
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
        return self::all()[$key]['label'] ?? ucfirst($key);
    }
}
