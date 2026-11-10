<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Subscription;
use Modules\Bookings\Mail\CustomerMessage;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\EmailTemplate;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class CustomerEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_pro_owner_can_manage_email_templates(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);

        $this->actingAs($owner)
            ->post(route('bookings.templates.store', ['current_team' => $team->slug]), [
                'name' => 'Seguimiento inicial',
                'subject' => 'Hola {{nombre}}',
                'body' => 'Te contactamos sobre {{terraza}}.',
            ])
            ->assertSessionHasNoErrors();

        $template = EmailTemplate::query()->firstOrFail();
        $this->assertSame($team->id, $template->team_id);

        $this->actingAs($owner)
            ->get(route('bookings.templates.index', ['current_team' => $team->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Templates/Index')
                ->has('templates', 1)
                ->where('templates.0.name', 'Seguimiento inicial'));

        $this->actingAs($owner)
            ->patch(route('bookings.templates.update', [
                'current_team' => $team->slug,
                'template' => $template->id,
            ]), [
                'name' => 'Seguimiento actualizado',
                'subject' => 'Nuevo asunto',
                'body' => 'Nuevo cuerpo',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Seguimiento actualizado', $template->fresh()->name);

        $this->actingAs($owner)
            ->delete(route('bookings.templates.destroy', [
                'current_team' => $team->slug,
                'template' => $template->id,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('email_templates', 0);
    }

    public function test_templates_are_isolated_by_team(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);
        $foreignTeam = Team::factory()->create();
        $foreignTemplate = EmailTemplate::factory()->create(['team_id' => $foreignTeam->id]);

        $this->actingAs($owner)
            ->patch(route('bookings.templates.update', [
                'current_team' => $team->slug,
                'template' => $foreignTemplate->id,
            ]), [
                'name' => 'Intruso',
                'subject' => 'Asunto',
                'body' => 'Cuerpo',
            ])
            ->assertNotFound();
    }

    public function test_owner_can_send_a_rendered_email_and_it_is_logged(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create([
            'team_id' => $team->id,
            'name' => 'Ana Torres',
            'listing_id' => $listing->id,
        ]);
        $template = EmailTemplate::factory()->create([
            'team_id' => $team->id,
            'subject' => 'Seguimiento a {{nombre}}',
            'body' => 'Hola {{nombre}}, te contactamos sobre {{terraza}}.',
        ]);

        Mail::fake();

        $this->actingAs($owner)
            ->post(route('bookings.customers.emails.send', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]), [
                'template_id' => $template->id,
                'subject' => $template->subject,
                'body' => $template->body,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        Mail::assertQueued(CustomerMessage::class, function (CustomerMessage $mail) use ($listing): bool {
            return $mail->subjectLine === 'Seguimiento a Ana Torres'
                && str_contains($mail->body, 'Hola Ana Torres')
                && str_contains($mail->body, $listing->name);
        });

        $this->assertDatabaseHas('customer_activities', [
            'customer_id' => $customer->id,
            'body' => 'Correo enviado: Seguimiento a Ana Torres.',
        ]);
    }

    public function test_email_is_blocked_when_customer_opted_out(): void
    {
        [$owner, $team] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create([
            'team_id' => $team->id,
            'email_opt_out_at' => now(),
        ]);

        Mail::fake();

        $this->actingAs($owner)
            ->post(route('bookings.customers.emails.send', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]), [
                'subject' => 'Hola',
                'body' => 'Mensaje',
            ])
            ->assertSessionHasErrors('email');

        Mail::assertNothingQueued();
    }

    public function test_signed_unsubscribe_link_opts_customer_out(): void
    {
        [$owner, $team] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $url = URL::signedRoute('public.customers.unsubscribe', [
            'customer' => $customer->id,
        ]);

        $this->get($url)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Public/Unsubscribed')
                ->where('customerName', $customer->name));

        $this->assertNotNull($customer->fresh()->email_opt_out_at);
    }

    public function test_unsigned_unsubscribe_link_is_rejected(): void
    {
        [$owner, $team] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $this->get(route('public.customers.unsubscribe', ['customer' => $customer->id]))
            ->assertForbidden();

        $this->assertNull($customer->fresh()->email_opt_out_at);
    }

    public function test_free_plan_cannot_manage_templates_or_send_email(): void
    {
        [$owner, $team] = $this->ownerListing();
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $this->actingAs($owner)
            ->get(route('bookings.templates.index', ['current_team' => $team->slug]))
            ->assertRedirect(route('billing.index', ['current_team' => $team->slug]));

        $this->actingAs($owner)
            ->post(route('bookings.customers.emails.send', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]), [
                'subject' => 'Hola',
                'body' => 'Mensaje',
            ])
            ->assertRedirect(route('billing.index', ['current_team' => $team->slug]));
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
     * @return array{0: User, 1: Team}
     */
    private function ownerWithTeam(): array
    {
        $owner = User::factory()->create();

        return [$owner, $owner->currentTeam];
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
