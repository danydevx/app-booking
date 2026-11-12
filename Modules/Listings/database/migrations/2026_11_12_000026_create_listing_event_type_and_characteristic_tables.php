<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_event_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('icon', 50)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('listing_characteristics', function (Blueprint $table) {
            $table->id();
            $table->string('service_key', 50)->unique();
            $table->string('description')->nullable();
            $table->string('icon', 50)->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('listing_event_type_listing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_event_type_id')
                ->constrained('listing_event_types')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['listing_id', 'listing_event_type_id'],
                'listing_event_type_unique',
            );
            $table->index(
                ['listing_event_type_id', 'listing_id'],
                'listing_event_type_listing_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_event_type_listing');
        Schema::dropIfExists('listing_characteristics');
        Schema::dropIfExists('listing_event_types');
    }
};
