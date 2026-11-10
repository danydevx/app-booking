<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('assigned_to')
                ->nullable()
                ->after('listing_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['team_id', 'assigned_to']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropIndex(['team_id', 'assigned_to']);
            $table->dropColumn('assigned_to');
        });
    }
};
