<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Listings\Database\Seeders\ListingCatalogSeeder;
use Modules\Platform\Database\Seeders\GeographicCatalogSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            GeographicCatalogSeeder::class,
            ListingCatalogSeeder::class,
            DemoUsersWithListingsSeeder::class,
            JaliscoTerracesSeeder::class,
        ]);
    }
}
