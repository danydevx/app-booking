<?php

namespace Tests\Unit\Inertia;

use App\Support\Inertia\PageAsset;
use Tests\TestCase;

class PageAssetTest extends TestCase
{
    public function test_core_page_paths_are_resolved_from_resources(): void
    {
        $path = PageAsset::path('Dashboard');

        $this->assertSame('resources/js/pages/Dashboard.vue', $path);
        $this->assertFileExists(base_path($path));
    }

    public function test_module_page_paths_are_resolved_from_the_module_folder(): void
    {
        $path = PageAsset::path('Platform/Dashboard');

        $this->assertSame(
            'Modules/Platform/resources/js/pages/Dashboard.vue',
            $path,
        );
        $this->assertFileExists(base_path($path));
    }

    public function test_listings_module_pages_are_resolved_from_the_module_folder(): void
    {
        $path = PageAsset::path('Listings/Index');

        $this->assertSame('Modules/Listings/resources/js/pages/Index.vue', $path);
        $this->assertFileExists(base_path($path));
    }

    public function test_missing_module_pages_fall_back_to_the_core_path(): void
    {
        $this->assertSame(
            'resources/js/pages/Platform/Missing.vue',
            PageAsset::path('Platform/Missing'),
        );
    }
}
