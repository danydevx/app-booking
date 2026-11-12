<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_inquiries', function (Blueprint $table) {
            $table->string('date_mode', 20)->default('exact')->after('end_date');
            $table->string('event_month', 7)->nullable()->after('date_mode');
            $table->string('guest_range', 20)->nullable()->after('guests');
        });
    }

    public function down(): void
    {
        Schema::table('booking_inquiries', function (Blueprint $table) {
            $table->dropColumn(['date_mode', 'event_month', 'guest_range']);
        });
    }
};
