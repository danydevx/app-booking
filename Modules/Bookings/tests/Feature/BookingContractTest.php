<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Subscription;
use Modules\Bookings\Enums\ContractStatus;
use Modules\Bookings\Models\BookingContract;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Notifications\BookingContractAccepted;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class BookingContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_pro_owner_can_create_and_publish_a_contract(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $inquiry = BookingInquiry::factory()->forListing($listing)->create([
            'customer_id' => $customer->id,
        ]);

        $this->actingAs($owner)
            ->post(route('bookings.contracts.store', [
                'current_team' => $team->slug,
                'inquiry' => $inquiry->id,
            ]), [
                'title' => 'Contrato de renta',
                'body' => 'El anticipo no es reembolsable.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $contract = BookingContract::query()->firstOrFail();

        $this->assertSame($team->id, $contract->team_id);
        $this->assertSame($inquiry->id, $contract->booking_inquiry_id);
        $this->assertSame(ContractStatus::Draft, $contract->status);

        $this->actingAs($owner)
            ->post(route('bookings.contracts.publish', [
                'current_team' => $team->slug,
                'contract' => $contract->id,
            ]))
            ->assertSessionHasNoErrors();

        $contract->refresh();

        $this->assertSame(ContractStatus::Sent, $contract->status);
        $this->assertNotNull($contract->sent_at);
        $this->assertDatabaseHas('customer_activities', [
            'customer_id' => $customer->id,
            'body' => 'Contrato publicado en el portal del cliente.',
        ]);
    }

    public function test_owner_can_update_a_draft_contract(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $inquiry = BookingInquiry::factory()->forListing($listing)->create();
        $contract = BookingContract::factory()->forInquiry($inquiry)->create([
            'title' => 'Título anterior',
        ]);

        $this->actingAs($owner)
            ->patch(route('bookings.contracts.update', [
                'current_team' => $team->slug,
                'contract' => $contract->id,
            ]), [
                'title' => 'Título nuevo',
                'body' => 'Cuerpo nuevo.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Título nuevo', $contract->fresh()->title);
    }

    public function test_free_plan_cannot_create_contracts(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $inquiry = BookingInquiry::factory()->forListing($listing)->create();

        $this->actingAs($owner)
            ->post(route('bookings.contracts.store', [
                'current_team' => $team->slug,
                'inquiry' => $inquiry->id,
            ]), [
                'title' => 'Contrato',
                'body' => 'Cuerpo.',
            ])
            ->assertRedirect(route('billing.index', ['current_team' => $team->slug]));

        $this->assertDatabaseCount('booking_contracts', 0);
    }

    public function test_contracts_are_isolated_by_team(): void
    {
        [$owner, $team] = $this->ownerListing();
        $this->subscribePro($team);
        $foreignInquiry = BookingInquiry::factory()->create();
        $foreignContract = BookingContract::factory()->forInquiry($foreignInquiry)->create();

        $this->actingAs($owner)
            ->patch(route('bookings.contracts.update', [
                'current_team' => $team->slug,
                'contract' => $foreignContract->id,
            ]), [
                'title' => 'Intruso',
                'body' => 'Cuerpo.',
            ])
            ->assertNotFound();
    }

    public function test_customer_can_accept_a_published_contract_in_the_portal(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $inquiry = BookingInquiry::factory()->forListing($listing)->create([
            'customer_id' => $customer->id,
        ]);
        $contract = BookingContract::factory()->forInquiry($inquiry)->create([
            'status' => ContractStatus::Sent,
            'sent_at' => now(),
        ]);

        Notification::fake();

        $this->get(route('public.bookings.portal', ['token' => $inquiry->portal_token]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('booking.contract.id', $contract->id)
                ->where('booking.contract.can_accept', true));

        $this->post(route('public.bookings.portal.contract.accept', [
            'token' => $inquiry->portal_token,
        ]), [
            'name' => 'Ana Acepta',
            'accepted' => true,
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $contract->refresh();

        $this->assertSame(ContractStatus::Accepted, $contract->status);
        $this->assertSame('Ana Acepta', $contract->accepted_by_name);
        $this->assertNotNull($contract->accepted_at);
        $this->assertNotNull($contract->accepted_ip_hash);

        $this->assertDatabaseHas('customer_activities', [
            'customer_id' => $customer->id,
            'body' => 'El cliente aceptó el contrato de la reserva.',
        ]);

        Notification::assertSentTo($owner, BookingContractAccepted::class);
    }

    public function test_draft_contract_is_not_visible_in_the_public_contract_page(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $inquiry = BookingInquiry::factory()->forListing($listing)->create();
        BookingContract::factory()->forInquiry($inquiry)->create();

        $this->get(route('public.bookings.portal.contract', [
            'token' => $inquiry->portal_token,
        ]))->assertNotFound();
    }

    public function test_contract_cannot_be_accepted_twice(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $inquiry = BookingInquiry::factory()->forListing($listing)->create();
        $contract = BookingContract::factory()->forInquiry($inquiry)->create([
            'status' => ContractStatus::Accepted,
            'accepted_at' => now(),
            'accepted_by_name' => 'Primera Persona',
        ]);

        $this->post(route('public.bookings.portal.contract.accept', [
            'token' => $inquiry->portal_token,
        ]), [
            'name' => 'Segunda Persona',
            'accepted' => true,
        ])->assertSessionHasErrors('contract');

        $this->assertSame('Primera Persona', $contract->fresh()->accepted_by_name);
    }

    public function test_owner_booking_detail_includes_the_contract(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $inquiry = BookingInquiry::factory()->forListing($listing)->create();
        $contract = BookingContract::factory()->forInquiry($inquiry)->create([
            'status' => ContractStatus::Sent,
            'sent_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('bookings.show', [
                'current_team' => $team->slug,
                'inquiry' => $inquiry->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('inquiry.contract.id', $contract->id)
                ->where('inquiry.contract.status', 'sent')
                ->where('inquiry.contract.can_accept', true));
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
