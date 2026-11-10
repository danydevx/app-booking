<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('merged_into_id')
                ->nullable()
                ->after('pipeline_stage')
                ->constrained('customers')
                ->nullOnDelete();
            $table->timestamp('merged_at')->nullable()->after('merged_into_id');
            $table->timestamp('archived_at')->nullable()->index()->after('merged_at');
            $table->timestamp('anonymized_at')->nullable()->after('archived_at');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['merged_into_id']);
            $table->dropIndex(['archived_at']);
            $table->dropColumn(['merged_into_id', 'merged_at', 'archived_at', 'anonymized_at']);
        });
    }
};
