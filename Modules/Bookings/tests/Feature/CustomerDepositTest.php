<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Bookings\Enums\DepositMethod;
use Modules\Bookings\Enums\DepositStatus;
use Modules\Bookings\Models\BookingDeposit;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class CustomerDepositTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_visitor_email_creates_separate_customers_per_account(): void
    {
        Notification::fake();

        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        $listingA = Listing::factory()->forTeam($teamA)->published()->create();
        $listingB = Listing::factory()->forTeam($teamB)->published()->create();

        foreach ([$listingA, $listingB] as $listing) {
            $this->post(
                route('public.bookings.inquiries.store', ['listing' => $listing->slug]),
                $this->publicPayload(),
            )->assertRedirect();
        }

        $this->assertSame(2, Customer::query()->where('email', 'visitante@example.test')->count());
        $this->assertDatabaseHas('customers', ['team_id' => $teamA->id, 'email' => 'visitante@example.test']);
        $this->assertDatabaseHas('customers', ['team_id' => $teamB->id, 'email' => 'visitante@example.test']);
    }

    public function test_owner_can_record_and_update_a_manual_deposit(): void
    {
        Notification::fake();

        [$owner, $team, $listing] = $this->ownerListing();
        $inquiry = BookingInquiry::factory()->forListing($listing)->create();

        $this->actingAs($owner)->post(route('bookings.deposits.store', [
            'current_team' => $team->slug,
            'inquiry' => $inquiry->id,
        ]), [
            'amount' => '1500.50',
            'status' => DepositStatus::Pending->value,
            'method' => DepositMethod::Transfer->value,
            'reference' => 'SPEI-123',
            'notes' => 'Anticipo acordado por WhatsApp.',
        ])->assertRedirect();

        $deposit = BookingDeposit::query()->firstOrFail();

        $this->assertSame($inquiry->id, $deposit->booking_inquiry_id);
        $this->assertSame(150050, $deposit->amount_cents);
        $this->assertSame('MXN', $deposit->currency);
        $this->assertSame(DepositStatus::Pending, $deposit->status);
        $this->assertSame(DepositMethod::Transfer, $deposit->method);

        $this->actingAs($owner)->patch(route('bookings.deposits.update', [
            'current_team' => $team->slug,
            'inquiry' => $inquiry->id,
            'deposit' => $deposit->id,
        ]), [
            'status' => DepositStatus::Received->value,
            'reference' => 'SPEI-123',
        ])->assertRedirect();

        $deposit->refresh();

        $this->assertSame(DepositStatus::Received, $deposit->status);
        $this->assertNotNull($deposit->received_at);
    }

    public function test_owner_can_update_customer_notes_but_not_another_account_customer(): void
    {
        [$owner, $team] = $this->ownerListing();
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $this->actingAs($owner)->patch(route('bookings.customers.update', [
            'current_team' => $team->slug,
            'customer' => $customer->id,
        ]), [
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'notes' => 'Cliente recurrente.',
        ])->assertRedirect();

        $this->assertSame('Cliente recurrente.', $customer->fresh()->notes);

        $otherTeam = Team::factory()->create();
        $foreignCustomer = Customer::factory()->create(['team_id' => $otherTeam->id]);

        $this->actingAs($owner)->patch(route('bookings.customers.update', [
            'current_team' => $team->slug,
            'customer' => $foreignCustomer->id,
        ]), [
            'name' => $foreignCustomer->name,
            'email' => $foreignCustomer->email,
            'notes' => 'No debería cambiar.',
        ])->assertNotFound();
    }

    public function test_foreign_deposit_cannot_be_updated_from_another_account(): void
    {
        [$owner, $team] = $this->ownerListing();
        $otherTeam = Team::factory()->create();
        $otherListing = Listing::factory()->forTeam($otherTeam)->create();
        $foreignInquiry = BookingInquiry::factory()->forListing($otherListing)->create();
        $foreignDeposit = BookingDeposit::factory()->create([
            'booking_inquiry_id' => $foreignInquiry->id,
        ]);

        $this->actingAs($owner)->patch(route('bookings.deposits.update', [
            'current_team' => $team->slug,
            'inquiry' => $foreignInquiry->id,
            'deposit' => $foreignDeposit->id,
        ]), [
            'status' => DepositStatus::Received->value,
        ])->assertNotFound();

        $this->assertSame(DepositStatus::Pending, $foreignDeposit->fresh()->status);
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

    /**
     * @return array<string, mixed>
     */
    private function publicPayload(): array
    {
        return [
            'name' => 'Visitante Prueba',
            'email' => 'visitante@example.test',
            'phone' => '5555555555',
            'whatsapp_phone' => null,
            'start_date' => now()->addDays(20)->toDateString(),
            'end_date' => now()->addDays(21)->toDateString(),
            'event_type' => 'Reunión',
            'guests' => 20,
            'message' => 'Me interesa el espacio.',
            'consent' => true,
            'website' => '',
        ];
    }
}
