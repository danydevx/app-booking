<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->after('timezone')->index();
            $table->text('suspension_reason')->nullable()->after('suspended_at');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropIndex(['suspended_at']);
            $table->dropColumn(['suspended_at', 'suspension_reason']);
        });
    }
};
