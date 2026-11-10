<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('promotional_price_cents')->nullable();
            $table->unsignedBigInteger('previous_price_cents')->nullable();
            $table->date('starts_at');
            $table->date('ends_at');
            $table->json('applies_on')->nullable();
            $table->text('conditions')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['listing_id', 'active', 'starts_at', 'ends_at']);
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_promotions');
    }
};
