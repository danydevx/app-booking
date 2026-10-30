<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_unavailability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('status')->default('blocked');
            $table->string('reason')->nullable();
            $table->foreignId('booking_inquiry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['listing_id', 'date']);
            $table->index(['listing_id', 'status', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_unavailability');
    }
};
