<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Platform\Database\Seeders\GeographicCatalogSeeder;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotent: seeds the catalog and creates default coverage rows only
        // when they do not exist. Existing Superadmin configuration is kept.
        app(GeographicCatalogSeeder::class)->run();
    }

    public function down(): void
    {
        // Reference data is intentionally not destroyed on rollback.
    }
};
