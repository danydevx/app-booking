<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('whatsapp_phone')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('event_type')->nullable();
            $table->unsignedInteger('guests')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('new')->index();
            $table->string('source')->default('public_web');
            $table->timestamp('consent_at');
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status', 'created_at']);
            $table->index(['listing_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_inquiries');
    }
};
