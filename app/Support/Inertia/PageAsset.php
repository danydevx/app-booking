<?php

namespace App\Support\Inertia;

final class PageAsset
{
    /**
     * Resolve the Vite source path for an Inertia page component.
     *
     * Core pages live in resources/js/pages. Business modules may override
     * the same component namespace from Modules/{Module}/resources/js/pages.
     */
    public static function path(string $component): string
    {
        $segments = explode('/', $component, 2);

        if (count($segments) === 2) {
            $modulePath = sprintf(
                'Modules/%s/resources/js/pages/%s.vue',
                $segments[0],
                $segments[1],
            );

            if (is_file(base_path($modulePath))) {
                return $modulePath;
            }
        }

        return "resources/js/pages/{$component}.vue";
    }
}
