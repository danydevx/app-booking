<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_price_ranges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->unsignedBigInteger('price_cents');
            $table->unsignedSmallInteger('included_hours')->nullable();
            $table->unsignedInteger('included_people')->nullable();
            $table->unsignedBigInteger('extra_hour_cents')->nullable();
            $table->unsignedBigInteger('extra_person_cents')->nullable();
            $table->unsignedBigInteger('deposit_required_cents')->nullable();
            $table->text('conditions')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['listing_id', 'active', 'starts_at', 'ends_at']);
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_price_ranges');
    }
};
