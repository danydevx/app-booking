<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_price_ranges', function (Blueprint $table) {
            $table->json('daily_pricing')->nullable()->after('price_cents');
        });

        DB::table('listing_price_ranges')
            ->orderBy('id')
            ->chunkById(100, function ($ranges): void {
                foreach ($ranges as $range) {
                    $daily = [];

                    foreach ([
                        'monday',
                        'tuesday',
                        'wednesday',
                        'thursday',
                        'friday',
                        'saturday',
                        'sunday',
                    ] as $day) {
                        $daily[$day] = [
                            'hours' => $range->included_hours,
                            'entry_time' => null,
                            'exit_time' => null,
                            'price_cents' => (int) $range->price_cents,
                        ];
                    }

                    DB::table('listing_price_ranges')
                        ->where('id', $range->id)
                        ->update(['daily_pricing' => json_encode($daily)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('listing_price_ranges', function (Blueprint $table) {
            $table->dropColumn('daily_pricing');
        });
    }
};
