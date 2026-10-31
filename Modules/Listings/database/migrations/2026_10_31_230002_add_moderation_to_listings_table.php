<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->after('published_at')->index();
            $table->string('suspended_from_status')->nullable()->after('suspended_at');
            $table->text('suspension_reason')->nullable()->after('suspended_from_status');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex(['suspended_at']);
            $table->dropColumn(['suspended_at', 'suspended_from_status', 'suspension_reason']);
        });
    }
};
