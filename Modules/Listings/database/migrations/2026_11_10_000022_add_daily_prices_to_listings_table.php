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
            $table->unsignedBigInteger('price_monday_cents')->nullable()->after('price_from_cents');
            $table->unsignedBigInteger('price_tuesday_cents')->nullable()->after('price_monday_cents');
            $table->unsignedBigInteger('price_wednesday_cents')->nullable()->after('price_tuesday_cents');
            $table->unsignedBigInteger('price_thursday_cents')->nullable()->after('price_wednesday_cents');
            $table->unsignedBigInteger('price_friday_cents')->nullable()->after('price_thursday_cents');
            $table->unsignedBigInteger('price_saturday_cents')->nullable()->after('price_friday_cents');
            $table->unsignedBigInteger('price_sunday_cents')->nullable()->after('price_saturday_cents');
        });

        // Preserve existing weekday/weekend pricing as daily values.
        DB::table('listings')
            ->whereNotNull('price_weekday_cents')
            ->update([
                'price_monday_cents' => DB::raw('price_weekday_cents'),
                'price_tuesday_cents' => DB::raw('price_weekday_cents'),
                'price_wednesday_cents' => DB::raw('price_weekday_cents'),
                'price_thursday_cents' => DB::raw('price_weekday_cents'),
            ]);

        DB::table('listings')
            ->whereNotNull('price_weekend_cents')
            ->update([
                'price_friday_cents' => DB::raw('price_weekend_cents'),
                'price_saturday_cents' => DB::raw('price_weekend_cents'),
                'price_sunday_cents' => DB::raw('price_weekend_cents'),
            ]);
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn([
                'price_monday_cents',
                'price_tuesday_cents',
                'price_wednesday_cents',
                'price_thursday_cents',
                'price_friday_cents',
                'price_saturday_cents',
                'price_sunday_cents',
            ]);
        });
    }
};
