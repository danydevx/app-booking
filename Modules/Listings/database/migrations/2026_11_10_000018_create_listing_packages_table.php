<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price_cents')->default(0);
            $table->unsignedSmallInteger('duration_hours');
            $table->unsignedInteger('included_people');
            $table->json('included_amenities')->nullable();
            $table->json('included_services')->nullable();
            $table->text('conditions')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['listing_id', 'active', 'sort_order']);
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_packages');
    }
};
