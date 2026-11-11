<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_services', function (Blueprint $table) {
            $table->string('pricing_mode', 20)->default('additional')->after('is_included');
            $table->string('provider', 20)->default('own')->after('pricing_mode');
        });

        DB::table('listing_services')
            ->orderBy('id')
            ->chunkById(100, function ($services): void {
                foreach ($services as $service) {
                    $mode = match (true) {
                        (bool) $service->is_included => 'included',
                        $service->price_cents === null => 'quote',
                        default => 'additional',
                    };

                    DB::table('listing_services')
                        ->where('id', $service->id)
                        ->update([
                            'pricing_mode' => $mode,
                            'provider' => 'own',
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('listing_services', function (Blueprint $table) {
            $table->dropColumn(['pricing_mode', 'provider']);
        });
    }
};
