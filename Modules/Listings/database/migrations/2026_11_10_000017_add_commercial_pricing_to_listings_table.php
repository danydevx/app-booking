<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->unsignedBigInteger('price_from_cents')->nullable()->after('capacity');
            $table->unsignedBigInteger('price_weekday_cents')->nullable()->after('price_from_cents');
            $table->unsignedBigInteger('price_weekend_cents')->nullable()->after('price_weekday_cents');
            $table->unsignedSmallInteger('included_hours')->nullable()->after('price_weekend_cents');
            $table->unsignedInteger('included_people')->nullable()->after('included_hours');
            $table->unsignedBigInteger('extra_hour_cents')->nullable()->after('included_people');
            $table->unsignedBigInteger('extra_person_cents')->nullable()->after('extra_hour_cents');
            $table->unsignedBigInteger('deposit_required_cents')->nullable()->after('extra_person_cents');
            $table->text('price_notes')->nullable()->after('deposit_required_cents');
            $table->string('currency', 3)->default('MXN')->after('price_notes');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn([
                'price_from_cents',
                'price_weekday_cents',
                'price_weekend_cents',
                'included_hours',
                'included_people',
                'extra_hour_cents',
                'extra_person_cents',
                'deposit_required_cents',
                'price_notes',
                'currency',
            ]);
        });
    }
};
