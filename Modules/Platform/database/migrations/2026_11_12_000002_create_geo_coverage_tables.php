<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_state_coverages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('geo_state_id')->unique()->constrained('geo_states')->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->boolean('all_municipalities')->default(false);
            $table->timestamps();
        });

        Schema::create('geo_municipality_coverages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('geo_municipality_id')->unique()->constrained('geo_municipalities')->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_municipality_coverages');
        Schema::dropIfExists('geo_state_coverages');
    }
};
