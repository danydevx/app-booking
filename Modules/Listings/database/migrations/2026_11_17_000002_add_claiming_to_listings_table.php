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
            $table->string('external_ref')->nullable()->after('slug')->unique();
            $table->timestamp('claimed_at')->nullable()->after('published_at')->index();
            $table->foreignId('claimed_by_user_id')
                ->nullable()
                ->after('claimed_at')
                ->constrained('users')
                ->nullOnDelete();
        });

        // Listings that already exist were created by their own account, so
        // they must never appear in the public "claim this terrace" flow.
        DB::table('listings')
            ->whereNull('claimed_at')
            ->update(['claimed_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropForeign(['claimed_by_user_id']);
            $table->dropUnique(['external_ref']);
            $table->dropIndex(['claimed_at']);
            $table->dropColumn(['external_ref', 'claimed_at', 'claimed_by_user_id']);
        });
    }
};
