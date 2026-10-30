<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_inquiry_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount_cents');
            $table->string('currency', 3)->default('MXN');
            $table->string('status')->default('pending')->index();
            $table->string('method')->default('cash');
            $table->string('reference')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['booking_inquiry_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_deposits');
    }
};
