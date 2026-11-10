<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('converted_inquiry_id')
                ->nullable()
                ->constrained('booking_inquiries')
                ->nullOnDelete();
            $table->string('public_token', 64)->unique();
            $table->string('status')->default('draft')->index();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('event_type')->nullable();
            $table->unsignedInteger('guests')->nullable();
            $table->unsignedBigInteger('subtotal_cents')->default(0);
            $table->unsignedBigInteger('deposit_cents')->default(0);
            $table->string('currency', 3)->default('MXN');
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status', 'created_at']);
            $table->index(['customer_id', 'created_at']);
            $table->index(['listing_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_quotes');
    }
};
