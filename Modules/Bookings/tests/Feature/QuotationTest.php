<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Subscription;
use Modules\Bookings\Enums\QuoteStatus;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerQuote;
use Modules\Bookings\Models\CustomerQuoteItem;
use Modules\Bookings\Support\QuoteCalculator;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingPackage;
use Modules\Listings\Models\ListingService;
use Tests\TestCase;

class QuotationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_pro_owner_can_create_a_draft_quotation_with_items_and_folio(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $this->actingAs($owner)
            ->post(route('bookings.quotes.store', ['current_team' => $team->slug]), $this->payload($listing, $customer))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $quote = CustomerQuote::query()->firstOrFail();

        $this->assertSame('COT-000001', $quote->folio);
        $this->assertSame(QuoteStatus::Draft, $quote->status);
        $this->assertSame($team->id, $quote->team_id);
        $this->assertSame($listing->id, $quote->listing_id);
        $this->assertSame($customer->id, $quote->customer_id);
        $this->assertSame(1600000, $quote->subtotal_cents);
        $this->assertSame(0, $quote->discount_cents);
        $this->assertSame(0, $quote->tax_cents);
        $this->assertSame(1600000, $quote->total_cents);
        $this->assertSame(3, CustomerQuoteItem::query()->where('customer_quote_id', $quote->id)->count());
        $this->assertDatabaseHas('customer_quote_status_histories', [
            'customer_quote_id' => $quote->id,
            'to_status' => QuoteStatus::Draft->value,
        ]);
    }

    public function test_free_plan_cannot_open_or_create_quotations(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $this->actingAs($owner)
            ->get(route('bookings.quotes.create', ['current_team' => $team->slug]))
            ->assertRedirect(route('billing.index', ['current_team' => $team->slug]));

        $this->actingAs($owner)
            ->post(route('bookings.quotes.store', ['current_team' => $team->slug]), $this->payload($listing, $customer))
            ->assertRedirect(route('billing.index', ['current_team' => $team->slug]));

        $this->assertDatabaseCount('customer_quotes', 0);
    }

    public function test_quotation_module_is_isolated_between_teams(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $quote = $this->createQuote($owner, $team, $listing, $customer);

        $foreignOwner = User::factory()->create();
        $foreignTeam = $foreignOwner->currentTeam;
        $this->subscribePro($foreignTeam);

        $this->actingAs($foreignOwner)
            ->get(route('bookings.quotes.edit', [
                'current_team' => $foreignTeam->slug,
                'quote' => $quote->id,
            ]))
            ->assertNotFound();

        $this->actingAs($foreignOwner)
            ->get(route('bookings.quotes.pdf', [
                'current_team' => $foreignTeam->slug,
                'quote' => $quote->id,
            ]))
            ->assertNotFound();

        $this->actingAs($foreignOwner)
            ->patch(route('bookings.quotes.update', [
                'current_team' => $foreignTeam->slug,
                'quote' => $quote->id,
            ]), $this->payload($listing, $customer))
            ->assertNotFound();
    }

    public function test_builder_exposes_listing_services_and_packages(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);

        ListingService::factory()->forListing($listing)->create([
            'service_key' => 'dj',
            'price_cents' => 250000,
        ]);
        ListingPackage::factory()->forListing($listing)->create([
            'name' => 'Paquete premium',
            'price_cents' => 500000,
            'included_services' => ['dj'],
        ]);

        $this->actingAs($owner)
            ->get(route('bookings.quotes.create', ['current_team' => $team->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Quotes/Builder')
                ->where('listings.0.services.0.price_cents', 250000)
                ->where('listings.0.packages.0.name', 'Paquete premium')
                ->where('listings.0.packages.0.services.0.key', 'dj'));
    }

    public function test_hourly_daily_and_fixed_rental_modes_are_stored(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $hourly = $this->createQuote($owner, $team, $listing, $customer, [
            'rental_mode' => 'hour',
            'start_time' => '18:00',
            'end_time' => '23:00',
            'billing_quantity' => 5,
            'items' => [[
                'type' => 'rental',
                'description' => 'Renta por hora',
                'unit' => 'hour',
                'quantity' => 5,
                'unit_price_cents' => 150000,
            ]],
        ]);

        $this->assertSame('hour', $hourly->rental_mode);
        $this->assertStringStartsWith('18:00', (string) $hourly->start_time);
        $this->assertSame(5, $hourly->billing_quantity);
        $this->assertSame(750000, $hourly->total_cents);

        $daily = $this->createQuote($owner, $team, $listing, $customer, [
            'rental_mode' => 'day',
            'billing_quantity' => 3,
            'items' => [[
                'type' => 'rental',
                'description' => 'Renta por día',
                'unit' => 'day',
                'quantity' => 3,
                'unit_price_cents' => 500000,
            ]],
        ]);

        $this->assertSame('day', $daily->rental_mode);
        $this->assertSame(3, $daily->billing_quantity);
        $this->assertSame(1500000, $daily->total_cents);

        $fixed = $this->createQuote($owner, $team, $listing, $customer, [
            'rental_mode' => 'fixed',
            'billing_quantity' => 1,
            'items' => [[
                'type' => 'rental',
                'description' => 'Precio fijo por evento',
                'unit' => 'event',
                'quantity' => 1,
                'unit_price_cents' => 2200000,
            ]],
        ]);

        $this->assertSame('fixed', $fixed->rental_mode);
        $this->assertSame(2200000, $fixed->total_cents);
    }

    public function test_universal_items_are_independent_and_keep_their_order(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $quote = $this->createQuote($owner, $team, $listing, $customer, [
            'items' => [
                [
                    'type' => 'rental',
                    'description' => 'Renta de Terraza Jardín Roma',
                    'details' => 'Renta por hora · 11 horas',
                    'unit' => 'hour',
                    'quantity' => 11,
                    'unit_price_cents' => 150000,
                ],
                [
                    'type' => 'service',
                    'description' => 'Servicio de sonido',
                    'details' => 'DJ y bocinas',
                    'unit' => 'service',
                    'quantity' => 1,
                    'unit_price_cents' => 300000,
                ],
                [
                    'type' => 'product',
                    'description' => 'Refrescos',
                    'details' => 'Lata 355 ml',
                    'unit' => 'piece',
                    'quantity' => 100,
                    'unit_price_cents' => 2500,
                ],
                [
                    'type' => 'custom',
                    'description' => 'Cargo adicional de limpieza',
                    'details' => null,
                    'unit' => 'event',
                    'quantity' => 1,
                    'unit_price_cents' => 100000,
                ],
            ],
        ]);

        $items = CustomerQuoteItem::query()
            ->where('customer_quote_id', $quote->id)
            ->orderBy('sort_order')
            ->get();

        $this->assertCount(4, $items);
        $this->assertSame(
            ['Renta de Terraza Jardín Roma', 'Servicio de sonido', 'Refrescos', 'Cargo adicional de limpieza'],
            $items->pluck('description')->all(),
        );
        $this->assertSame(
            ['rental', 'service', 'product', 'custom'],
            $items->pluck('type')->all(),
        );
        $this->assertSame(
            ['hour', 'service', 'piece', 'event'],
            $items->pluck('unit')->all(),
        );
        $this->assertSame([11, 1, 100, 1], $items->pluck('quantity')->all());
        $this->assertSame([0, 1, 2, 3], $items->pluck('sort_order')->all());
        $this->assertSame(
            150000 * 11 + 300000 + 2500 * 100 + 100000,
            $quote->total_cents,
        );
    }

    public function test_editing_one_item_does_not_alter_other_items(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $quote = $this->createQuote($owner, $team, $listing, $customer, [
            'items' => [
                [
                    'type' => 'service',
                    'description' => 'Servicio de sonido',
                    'unit' => 'service',
                    'quantity' => 1,
                    'unit_price_cents' => 300000,
                ],
                [
                    'type' => 'product',
                    'description' => 'Refrescos',
                    'unit' => 'piece',
                    'quantity' => 100,
                    'unit_price_cents' => 2500,
                ],
            ],
        ]);

        $this->actingAs($owner)
            ->patch(route('bookings.quotes.update', [
                'current_team' => $team->slug,
                'quote' => $quote->id,
            ]), $this->payload($listing, $customer, [
                'items' => [
                    [
                        'type' => 'service',
                        'description' => 'Servicio de sonido actualizado',
                        'unit' => 'service',
                        'quantity' => 1,
                        'unit_price_cents' => 350000,
                    ],
                    [
                        'type' => 'product',
                        'description' => 'Refrescos',
                        'unit' => 'piece',
                        'quantity' => 100,
                        'unit_price_cents' => 2500,
                    ],
                ],
            ]))
            ->assertSessionHasNoErrors();

        $items = CustomerQuoteItem::query()
            ->where('customer_quote_id', $quote->id)
            ->orderBy('sort_order')
            ->get();

        $this->assertCount(2, $items);
        $this->assertSame('Servicio de sonido actualizado', $items[0]->description);
        $this->assertSame(350000, $items[0]->unit_price_cents);
        $this->assertSame('Refrescos', $items[1]->description);
        $this->assertSame(250000, $items[1]->amount_cents);
        $this->assertSame(600000, $quote->refresh()->total_cents);
    }

    public function test_invalid_item_fields_are_rejected_by_the_backend(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $this->actingAs($owner)
            ->post(route('bookings.quotes.store', ['current_team' => $team->slug]), $this->payload($listing, $customer, [
                'items' => [[
                    'type' => 'service',
                    'description' => '',
                    'unit' => 'service',
                    'quantity' => 0,
                    'unit_price_cents' => -1,
                ]],
            ]))
            ->assertSessionHasErrors([
                'items.0.description',
                'items.0.quantity',
                'items.0.unit_price_cents',
            ]);

        $this->assertDatabaseCount('customer_quotes', 0);
    }

    public function test_tax_modes_are_calculated_in_cents(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $plain = $this->createQuote($owner, $team, $listing, $customer, ['tax_mode' => 'none']);
        $this->assertSame(0, $plain->tax_cents);
        $this->assertSame(1600000, $plain->total_cents);

        $added = $this->createQuote($owner, $team, $listing, $customer, ['tax_mode' => 'added']);
        $this->assertSame(256000, $added->tax_cents);
        $this->assertSame(1856000, $added->total_cents);

        $included = $this->createQuote($owner, $team, $listing, $customer, ['tax_mode' => 'included']);
        $this->assertSame(220690, $included->tax_cents);
        $this->assertSame(1600000, $included->total_cents);
        $this->assertSame(1600000, $included->subtotal_cents);
        $this->assertSame(1600000 - 220690, $included->total_cents - $included->tax_cents);
    }

    public function test_discounts_cannot_exceed_the_eligible_amount(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $discounted = $this->createQuote($owner, $team, $listing, $customer, [
            'tax_mode' => 'added',
            'discount_type' => 'fixed',
            'discount_value' => 100000,
        ]);

        $this->assertSame(100000, $discounted->discount_cents);
        $this->assertSame(240000, $discounted->tax_cents);
        $this->assertSame(1740000, $discounted->total_cents);

        $percent = $this->createQuote($owner, $team, $listing, $customer, [
            'discount_type' => 'percent',
            'discount_value' => 1000,
        ]);

        $this->assertSame(160000, $percent->discount_cents);
        $this->assertSame(1440000, $percent->total_cents);

        $this->actingAs($owner)
            ->post(route('bookings.quotes.store', ['current_team' => $team->slug]), $this->payload($listing, $customer, [
                'discount_type' => 'fixed',
                'discount_value' => 1600001,
            ]))
            ->assertSessionHasErrors('discount_value');
    }

    public function test_quote_calculator_rounds_deterministically(): void
    {
        $calculator = app(QuoteCalculator::class);

        $added = $calculator->calculate([999], 'added', 1600, 'none', 0);
        $this->assertSame(160, $added['tax_cents']);
        $this->assertSame(1159, $added['total_cents']);

        $included = $calculator->calculate([999], 'included', 1600, 'none', 0);
        $this->assertSame(138, $included['tax_cents']);
        $this->assertSame(999, $included['total_cents']);

        $none = $calculator->calculate([999, 1001], 'none', 1600, 'fixed', 500);
        $this->assertSame(2000, $none['subtotal_cents']);
        $this->assertSame(500, $none['discount_cents']);
        $this->assertSame(1500, $none['total_cents']);
    }

    public function test_folios_are_unique_and_sequential_per_team(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $first = $this->createQuote($owner, $team, $listing, $customer);
        $second = $this->createQuote($owner, $team, $listing, $customer);

        $this->assertSame('COT-000001', $first->folio);
        $this->assertSame('COT-000002', $second->folio);
    }

    public function test_draft_can_be_edited_and_issued_without_overwriting(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $quote = $this->createQuote($owner, $team, $listing, $customer);

        $this->actingAs($owner)
            ->patch(route('bookings.quotes.update', [
                'current_team' => $team->slug,
                'quote' => $quote->id,
            ]), $this->payload($listing, $customer, [
                'notes' => 'Versión actualizada.',
                'tax_mode' => 'added',
            ]))
            ->assertSessionHasNoErrors();

        $quote->refresh();
        $this->assertSame('Versión actualizada.', $quote->notes);
        $this->assertSame(1856000, $quote->total_cents);

        $this->actingAs($owner)
            ->post(route('bookings.quotes.issue', [
                'current_team' => $team->slug,
                'quote' => $quote->id,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('bookings.quotes.show', [
                'current_team' => $team->slug,
                'quote' => $quote->id,
            ]));

        $quote->refresh();
        $this->assertSame(QuoteStatus::Sent, $quote->status);
        $this->assertNotNull($quote->issued_at);
        $this->assertNotNull($quote->pdf_path);
        Storage::disk('local')->assertExists((string) $quote->pdf_path);
        $this->assertSame('Versión actualizada.', $quote->notes);
        $this->assertNotNull($quote->issuer_snapshot);
        $this->assertNotNull($quote->customer_snapshot);

        $this->actingAs($owner)
            ->patch(route('bookings.quotes.update', [
                'current_team' => $team->slug,
                'quote' => $quote->id,
            ]), $this->payload($listing, $customer, ['notes' => 'No debe cambiar']))
            ->assertSessionHasNoErrors();

        $quote->refresh();
        $this->assertSame('Versión actualizada.', $quote->notes);
        $this->assertSame(QuoteStatus::Sent, $quote->status);
    }

    public function test_issued_pdf_can_be_downloaded_only_by_its_team_and_keeps_snapshot(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $quote = $this->createQuote($owner, $team, $listing, $customer);
        $this->issue($owner, $team, $quote);

        $response = $this->actingAs($owner)->get(route('bookings.quotes.pdf', [
            'current_team' => $team->slug,
            'quote' => $quote->id,
        ]));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));

        $quote->refresh();
        $path = (string) $quote->pdf_path;
        $original = $quote->issuer_snapshot;

        // Cambiar datos de la terraza después de emitir no altera el snapshot.
        $listing->update(['name' => 'Terraza Renombrada', 'contact_phone' => '0000000000']);

        $this->actingAs($owner)->get(route('bookings.quotes.pdf', [
            'current_team' => $team->slug,
            'quote' => $quote->id,
        ]))->assertOk();

        $this->assertSame($path, $quote->refresh()->pdf_path);
        $this->assertSame($original, $quote->issuer_snapshot);
        Storage::disk('local')->assertExists($path);
    }

    public function test_issued_pdf_preserves_the_listing_logo_after_it_changes(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $path = 'listings/'.$listing->id.'/logo-test.png';
        Storage::disk('public')->put(
            $path,
            UploadedFile::fake()->image('logo.png', 120, 60)->get(),
        );
        $listing->update(['logo_path' => $path]);

        $quote = $this->createQuote($owner, $team, $listing, $customer);
        $this->issue($owner, $team, $quote);

        $quote->refresh();
        $snapshot = $quote->issuer_snapshot;

        $this->assertSame('local', $snapshot['logo_disk']);
        $this->assertNotSame($path, $snapshot['logo_path']);
        Storage::disk('local')->assertExists((string) $snapshot['logo_path']);

        Storage::disk('public')->delete($path);
        $listing->update(['logo_path' => null]);

        $this->actingAs($owner)
            ->get(route('bookings.quotes.preview', [
                'current_team' => $team->slug,
                'quote' => $quote->id,
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        Storage::disk('local')->assertExists((string) $quote->pdf_path);
        Storage::disk('local')->assertExists((string) $snapshot['logo_path']);
    }

    public function test_preview_and_pdf_are_multipage_for_many_items(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $items = [];

        for ($index = 1; $index <= 70; $index++) {
            $items[] = [
                'type' => 'service',
                'description' => "Servicio {$index}",
                'details' => str_repeat('Detalle del concepto. ', 4),
                'unit' => 'service',
                'quantity' => 1,
                'unit_price_cents' => 100000,
            ];
        }

        $quote = $this->createQuote($owner, $team, $listing, $customer, ['items' => $items]);

        $response = $this->actingAs($owner)->get(route('bookings.quotes.preview', [
            'current_team' => $team->slug,
            'quote' => $quote->id,
        ]));

        $response->assertOk();
        $binary = (string) $response->getContent();

        $this->assertStringStartsWith('%PDF', $binary);
        preg_match_all('/\/Type\s*\/Page[^s]/', $binary, $matches);
        $this->assertGreaterThan(1, count($matches[0]));
    }

    public function test_quote_can_create_a_customer_and_link_an_inquiry(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $inquiry = BookingInquiry::factory()->forListing($listing)->create();

        $payload = $this->payload($listing, null, [
            'customer_id' => null,
            'new_customer' => [
                'name' => 'Cliente Nuevo',
                'email' => 'nuevo@example.test',
                'phone' => '5550000000',
            ],
            'converted_inquiry_id' => $inquiry->id,
        ]);

        $this->actingAs($owner)
            ->post(route('bookings.quotes.store', ['current_team' => $team->slug]), $payload)
            ->assertSessionHasNoErrors();

        $quote = CustomerQuote::query()->firstOrFail();
        $customer = Customer::query()->where('email', 'nuevo@example.test')->firstOrFail();

        $this->assertSame($customer->id, $quote->customer_id);
        $this->assertSame($inquiry->id, $quote->converted_inquiry_id);
        $this->assertSame($team->id, $customer->team_id);
    }

    public function test_quote_index_filters_by_date_range(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $near = now()->addDays(10);
        $far = now()->addDays(40);

        $first = $this->createQuote($owner, $team, $listing, $customer, [
            'start_date' => $near->toDateString(),
            'end_date' => $near->toDateString(),
        ]);
        $second = $this->createQuote($owner, $team, $listing, $customer, [
            'start_date' => $far->toDateString(),
            'end_date' => $far->toDateString(),
        ]);

        $this->actingAs($owner)
            ->get(route('bookings.quotes.index', [
                'current_team' => $team->slug,
                'from' => $near->copy()->subDay()->toDateString(),
                'to' => $near->copy()->addDay()->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('quotes.data', 1)
                ->where('quotes.data.0.id', $first->id));

        $this->assertNotSame($first->id, $second->id);
    }

    public function test_losing_pro_access_keeps_quotes_but_blocks_the_module(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $quote = $this->createQuote($owner, $team, $listing, $customer);
        $this->issue($owner, $team, $quote);

        Subscription::query()->where('team_id', $team->id)->delete();

        $this->actingAs($owner)
            ->get(route('bookings.quotes.index', ['current_team' => $team->slug]))
            ->assertRedirect(route('billing.index', ['current_team' => $team->slug]));

        $this->actingAs($owner)
            ->get(route('bookings.quotes.edit', [
                'current_team' => $team->slug,
                'quote' => $quote->id,
            ]))
            ->assertRedirect(route('billing.index', ['current_team' => $team->slug]));

        $quote->refresh();

        $this->assertDatabaseHas('customer_quotes', [
            'id' => $quote->id,
            'pdf_path' => $quote->pdf_path,
        ]);
    }

    public function test_cancelled_quotes_keep_their_historical_data(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $quote = $this->createQuote($owner, $team, $listing, $customer);
        $this->issue($owner, $team, $quote);

        $this->actingAs($owner)
            ->post(route('bookings.quotes.cancel', [
                'current_team' => $team->slug,
                'quote' => $quote->id,
            ]))
            ->assertSessionHasNoErrors();

        $quote->refresh();

        $this->assertSame(QuoteStatus::Cancelled, $quote->status);
        $this->assertNotNull($quote->cancelled_at);
        $this->assertSame(1600000, $quote->total_cents);
        $this->assertDatabaseHas('customer_quote_status_histories', [
            'customer_quote_id' => $quote->id,
            'to_status' => QuoteStatus::Cancelled->value,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createQuote(
        User $owner,
        Team $team,
        Listing $listing,
        ?Customer $customer,
        array $overrides = [],
    ): CustomerQuote {
        $this->actingAs($owner)
            ->post(
                route('bookings.quotes.store', ['current_team' => $team->slug]),
                $this->payload($listing, $customer, $overrides),
            )
            ->assertSessionHasNoErrors();

        return CustomerQuote::query()->latest('id')->firstOrFail();
    }

    private function issue(User $owner, Team $team, CustomerQuote $quote): void
    {
        $this->actingAs($owner)
            ->post(route('bookings.quotes.issue', [
                'current_team' => $team->slug,
                'quote' => $quote->id,
            ]))
            ->assertSessionHasNoErrors();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Listing $listing, ?Customer $customer, array $overrides = []): array
    {
        $start = now($listing->timezone)->addDays(10);

        $defaults = [
            'listing_id' => $listing->id,
            'customer_id' => $customer?->id,
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDay()->toDateString(),
            'rental_mode' => 'hour',
            'start_time' => '12:00',
            'end_time' => '20:00',
            'billing_quantity' => 8,
            'event_type' => 'Evento corporativo',
            'guests' => 80,
            'tax_mode' => 'none',
            'discount_type' => 'none',
            'discount_value' => 0,
            'deposit_cents' => 0,
            'notes' => 'Incluye montaje.',
            'terms' => 'Anticipo del 50%.',
            'expires_at' => now()->addDays(15)->toDateString(),
            'items' => [
                [
                    'type' => 'rental',
                    'description' => 'Renta de terraza',
                    'unit' => 'hour',
                    'quantity' => 8,
                    'unit_price_cents' => 150000,
                ],
                [
                    'type' => 'service',
                    'description' => 'Mobiliario',
                    'unit' => 'service',
                    'quantity' => 1,
                    'unit_price_cents' => 300000,
                ],
                [
                    'type' => 'custom',
                    'description' => 'Limpieza',
                    'unit' => 'service',
                    'quantity' => 1,
                    'unit_price_cents' => 100000,
                ],
            ],
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * @return array{0: User, 1: Team, 2: Listing}
     */
    private function ownerListing(): array
    {
        $owner = User::factory()->create();
        $team = $owner->currentTeam;
        $listing = Listing::factory()->forTeam($team)->published()->create();

        return [$owner, $team, $listing];
    }

    private function subscribePro(Team $team): void
    {
        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);

        Subscription::query()->create([
            'team_id' => $team->id,
            'type' => 'default',
            'stripe_id' => 'sub_'.fake()->unique()->bothify('??####'),
            'stripe_status' => 'active',
            'stripe_price' => 'price_pro_test',
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);
    }
}
