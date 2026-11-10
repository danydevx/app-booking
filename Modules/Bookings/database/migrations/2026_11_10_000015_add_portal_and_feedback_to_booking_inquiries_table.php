<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_inquiries', function (Blueprint $table) {
            $table->string('portal_token', 64)->nullable()->unique()->after('ip_hash');
            $table->timestamp('customer_confirmed_at')->nullable()->after('portal_token');
            $table->unsignedTinyInteger('feedback_rating')->nullable()->after('customer_confirmed_at');
            $table->text('feedback_comment')->nullable()->after('feedback_rating');
            $table->timestamp('feedback_at')->nullable()->after('feedback_comment');
        });
    }

    public function down(): void
    {
        Schema::table('booking_inquiries', function (Blueprint $table) {
            $table->dropUnique(['portal_token']);
            $table->dropColumn([
                'portal_token',
                'customer_confirmed_at',
                'feedback_rating',
                'feedback_comment',
                'feedback_at',
            ]);
        });
    }
};
