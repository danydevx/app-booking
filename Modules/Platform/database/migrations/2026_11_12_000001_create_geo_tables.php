<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('geo_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('geo_country_id')->constrained('geo_countries')->cascadeOnDelete();
            $table->string('code', 4);
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['geo_country_id', 'code']);
            $table->index('name');
        });

        Schema::create('geo_municipalities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('geo_state_id')->constrained('geo_states')->cascadeOnDelete();
            $table->string('code', 4);
            $table->string('full_code', 6)->unique();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['geo_state_id', 'code']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_municipalities');
        Schema::dropIfExists('geo_states');
        Schema::dropIfExists('geo_countries');
    }
};
