<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->json('daily_pricing')->nullable()->after('price_sunday_cents');
        });

        DB::table('listings')
            ->orderBy('id')
            ->chunkById(100, function ($listings): void {
                foreach ($listings as $listing) {
                    $daily = [];

                    foreach ([
                        'monday' => $listing->price_monday_cents,
                        'tuesday' => $listing->price_tuesday_cents,
                        'wednesday' => $listing->price_wednesday_cents,
                        'thursday' => $listing->price_thursday_cents,
                        'friday' => $listing->price_friday_cents,
                        'saturday' => $listing->price_saturday_cents,
                        'sunday' => $listing->price_sunday_cents,
                    ] as $day => $priceCents) {
                        if ($priceCents === null) {
                            continue;
                        }

                        $daily[$day] = [
                            'hours' => $listing->included_hours,
                            'entry_time' => null,
                            'exit_time' => null,
                            'price_cents' => (int) $priceCents,
                        ];
                    }

                    if ($daily !== []) {
                        DB::table('listings')
                            ->where('id', $listing->id)
                            ->update(['daily_pricing' => json_encode($daily)]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('daily_pricing');
        });
    }
};
