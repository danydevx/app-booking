<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Listings\Database\Seeders\ListingCatalogSeeder;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotent: creates missing catalog rows without overwriting edits
        // made later by the Superadmin.
        app(ListingCatalogSeeder::class)->run();
    }

    public function down(): void
    {
        // Reference data is intentionally preserved on rollback.
    }
};
