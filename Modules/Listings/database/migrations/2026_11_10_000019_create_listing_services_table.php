<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->string('service_key', 80);
            $table->text('description')->nullable();
            $table->boolean('is_included')->default(false);
            $table->unsignedBigInteger('price_cents')->nullable();
            $table->string('price_unit', 20)->default('event');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['listing_id', 'service_key']);
            $table->index(['listing_id', 'active']);
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_services');
    }
};
