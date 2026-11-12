<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('customer_quotes', 'folio')) {
            Schema::table('customer_quotes', function (Blueprint $table) {
                $table->string('folio', 24)->nullable()->after('id');
                $table->string('rental_mode', 20)->default('fixed')->after('end_date');
                $table->time('start_time')->nullable()->after('rental_mode');
                $table->time('end_time')->nullable()->after('start_time');
                $table->unsignedSmallInteger('billing_quantity')->nullable()->after('end_time');
                $table->string('tax_mode', 20)->default('none')->after('billing_quantity');
                $table->unsignedSmallInteger('tax_rate_bps')->default(1600)->after('tax_mode');
                $table->unsignedBigInteger('tax_cents')->default(0)->after('tax_rate_bps');
                $table->string('discount_type', 20)->default('none')->after('tax_cents');
                $table->unsignedBigInteger('discount_value')->default(0)->after('discount_type');
                $table->unsignedBigInteger('discount_cents')->default(0)->after('discount_value');
                $table->unsignedBigInteger('total_cents')->nullable()->after('discount_cents');
                $table->json('issuer_snapshot')->nullable()->after('total_cents');
                $table->json('customer_snapshot')->nullable()->after('issuer_snapshot');
                $table->json('listing_snapshot')->nullable()->after('customer_snapshot');
                $table->string('pdf_path')->nullable()->after('listing_snapshot');
                $table->timestamp('issued_at')->nullable()->after('pdf_path');
                $table->timestamp('pdf_generated_at')->nullable()->after('issued_at');
                $table->timestamp('cancelled_at')->nullable()->after('declined_at');

                $table->unique(['team_id', 'folio']);
            });
        }

        if (! Schema::hasTable('quote_sequences')) {
            Schema::create('quote_sequences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id')->unique()->constrained()->cascadeOnDelete();
                $table->unsignedInteger('next_number')->default(1);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('customer_quote_items')) {
            Schema::create('customer_quote_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_quote_id')->constrained()->cascadeOnDelete();
                $table->foreignId('team_id')->constrained()->cascadeOnDelete();
                $table->string('type', 30)->default('custom');
                $table->string('description');
                $table->text('details')->nullable();
                $table->string('unit', 20)->default('service');
                $table->unsignedInteger('quantity')->default(1);
                $table->unsignedBigInteger('unit_price_cents')->default(0);
                $table->unsignedBigInteger('amount_cents')->default(0);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['customer_quote_id', 'sort_order']);
                $table->index(['team_id', 'customer_quote_id']);
            });
        }

        if (! Schema::hasTable('customer_quote_status_histories')) {
            Schema::create('customer_quote_status_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_quote_id')->constrained()->cascadeOnDelete();
                $table->foreignId('team_id')->constrained()->cascadeOnDelete();
                $table->string('from_status', 20)->nullable();
                $table->string('to_status', 20);
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('note')->nullable();
                $table->timestamps();

                // MySQL limita los nombres de índice a 64 caracteres.
                $table->index(
                    ['customer_quote_id', 'created_at'],
                    'quote_status_history_quote_created_index',
                );
            });
        } elseif (! $this->hasIndex('customer_quote_status_histories', 'quote_status_history_quote_created_index')) {
            Schema::table('customer_quote_status_histories', function (Blueprint $table) {
                $table->index(
                    ['customer_quote_id', 'created_at'],
                    'quote_status_history_quote_created_index',
                );
            });
        }

        // Las cotizaciones existentes reciben un folio histórico consecutivo
        // para que nunca se mezclen con la numeración nueva.
        $teamIds = DB::table('customer_quotes')
            ->whereNull('folio')
            ->distinct()
            ->pluck('team_id');

        foreach ($teamIds as $teamId) {
            $ids = DB::table('customer_quotes')
                ->where('team_id', $teamId)
                ->whereNull('folio')
                ->orderBy('id')
                ->pluck('id');

            $number = 1;

            foreach ($ids as $id) {
                DB::table('customer_quotes')
                    ->where('id', $id)
                    ->update([
                        'folio' => 'COT-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT),
                    ]);

                $number++;
            }

            DB::table('quote_sequences')->updateOrInsert(
                ['team_id' => $teamId],
                [
                    'next_number' => $number,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_quote_status_histories');
        Schema::dropIfExists('customer_quote_items');
        Schema::dropIfExists('quote_sequences');

        if (Schema::hasColumn('customer_quotes', 'folio')) {
            Schema::table('customer_quotes', function (Blueprint $table) {
                $table->dropUnique(['team_id', 'folio']);
                $table->dropColumn([
                    'folio',
                    'rental_mode',
                    'start_time',
                    'end_time',
                    'billing_quantity',
                    'tax_mode',
                    'tax_rate_bps',
                    'tax_cents',
                    'discount_type',
                    'discount_value',
                    'discount_cents',
                    'total_cents',
                    'issuer_snapshot',
                    'customer_snapshot',
                    'listing_snapshot',
                    'pdf_path',
                    'issued_at',
                    'pdf_generated_at',
                    'cancelled_at',
                ]);
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))
            ->pluck('name')
            ->contains($index);
    }
};
