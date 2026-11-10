<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('availability_block_series')) {
            Schema::create('availability_block_series', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id')->constrained()->cascadeOnDelete();
                $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
                $table->string('reason', 255)->nullable();
                $table->string('recurrence');
                $table->json('weekdays')->nullable();
                $table->date('starts_on');
                $table->date('ends_on');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['team_id', 'listing_id', 'starts_on']);
            });
        }

        if (! Schema::hasTable('availability_block_series_exceptions')) {
            Schema::create('availability_block_series_exceptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('availability_block_series_id');
                $table->date('date');
                $table->timestamps();

                $table->foreign('availability_block_series_id', 'abs_exceptions_series_foreign')
                    ->references('id')
                    ->on('availability_block_series')
                    ->cascadeOnDelete();
                $table->unique(['availability_block_series_id', 'date'], 'abs_exceptions_series_date_unique');
            });
        }

        if (! Schema::hasColumn('listing_unavailability', 'availability_block_series_id')) {
            Schema::table('listing_unavailability', function (Blueprint $table) {
                $table->foreignId('availability_block_series_id')
                    ->nullable()
                    ->after('booking_inquiry_id')
                    ->constrained('availability_block_series')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('listing_unavailability', function (Blueprint $table) {
            $table->dropForeign(['availability_block_series_id']);
            $table->dropColumn('availability_block_series_id');
        });

        Schema::dropIfExists('availability_block_series_exceptions');
        Schema::dropIfExists('availability_block_series');
    }
};
