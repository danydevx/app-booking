<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('slug');
            $table->string('contact_email')->nullable()->after('logo_path');
            $table->string('contact_phone', 40)->nullable()->after('contact_email');
            $table->string('whatsapp_phone', 40)->nullable()->after('contact_phone');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'contact_email', 'contact_phone', 'whatsapp_phone']);
        });
    }
};
