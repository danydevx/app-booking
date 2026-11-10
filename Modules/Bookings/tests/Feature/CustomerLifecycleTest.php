<?php

namespace Modules\Bookings\Tests\Feature;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use Modules\Bookings\Enums\CustomerTaskStatus;
use Modules\Bookings\Models\BookingDeposit;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerActivity;
use Modules\Bookings\Models\CustomerTask;
use Tests\TestCase;

class CustomerLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_archive_and_restore_a_customer(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $this->actingAs($owner)
            ->patch(route('bookings.customers.archive', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]))
            ->assertRedirect(route('bookings.customers.index', ['current_team' => $team->slug]));

        $this->assertNotNull($customer->fresh()->archived_at);

        $this->actingAs($owner)
            ->get(route('bookings.customers.index', ['current_team' => $team->slug]))
            ->assertInertia(fn ($page) => $page->has('customers.data', 0));

        $this->actingAs($owner)
            ->get(route('bookings.customers.index', [
                'current_team' => $team->slug,
                'archived' => 1,
            ]))
            ->assertInertia(fn ($page) => $page->has('customers.data', 1));

        $this->actingAs($owner)
            ->patch(route('bookings.customers.restore', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]))
            ->assertRedirect();

        $this->assertNull($customer->fresh()->archived_at);
    }

    public function test_anonymization_removes_personal_data_and_preserves_bookings(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);
        $customer = Customer::factory()->create([
            'team_id' => $team->id,
            'name' => 'Persona Real',
            'email' => 'persona@example.test',
            'phone' => '5555555555',
            'notes' => 'Nota privada',
        ]);
        $inquiry = BookingInquiry::factory()->forCustomer($customer)->create([
            'name' => 'Persona Real',
            'email' => 'persona@example.test',
            'phone' => '5555555555',
            'whatsapp_phone' => '5215555555555',
            'message' => 'Mensaje privado',
            'ip_hash' => 'hash',
        ]);
        BookingDeposit::factory()->create([
            'booking_inquiry_id' => $inquiry->id,
            'amount_cents' => 10000,
        ]);
        $activity = CustomerActivity::factory()->forCustomer($customer)->create([
            'body' => 'Llamada privada',
        ]);
        $task = CustomerTask::factory()->forCustomer($customer)->create([
            'title' => 'Tarea privada',
        ]);

        $this->actingAs($owner)
            ->patch(route('bookings.customers.anonymize', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]), [
                'reason' => 'Solicitud de eliminación de datos.',
            ])
            ->assertRedirect(route('bookings.customers.index', ['current_team' => $team->slug]));

        $customer->refresh();
        $inquiry->refresh();

        $this->assertStringStartsWith('Cliente anonimizado', $customer->name);
        $this->assertStringContainsString('@example.invalid', $customer->email);
        $this->assertNull($customer->phone);
        $this->assertNull($customer->notes);
        $this->assertNotNull($customer->anonymized_at);
        $this->assertNotNull($customer->archived_at);

        $this->assertSame('Cliente anonimizado', $inquiry->name);
        $this->assertStringContainsString('@example.invalid', $inquiry->email);
        $this->assertSame('0000000000', $inquiry->phone);
        $this->assertNull($inquiry->whatsapp_phone);
        $this->assertNull($inquiry->message);
        $this->assertNull($inquiry->ip_hash);

        $this->assertDatabaseHas('customer_activities', [
            'id' => $activity->id,
            'body' => '[Contenido eliminado por anonimización]',
        ]);
        $this->assertSame('Tarea de cliente anonimizado', $task->fresh()->title);
        $this->assertNull($task->fresh()->notes);
        $this->assertSame(CustomerTaskStatus::Cancelled, $task->fresh()->status);
        $this->assertDatabaseHas('booking_deposits', ['booking_inquiry_id' => $inquiry->id]);
    }

    public function test_lifecycle_actions_are_admin_only_and_team_scoped(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);
        $member = User::factory()->create();
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $this->actingAs($member)
            ->patch(route('bookings.customers.archive', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]))
            ->assertForbidden();

        $other = User::factory()->create();
        $foreign = Customer::factory()->create(['team_id' => $other->currentTeam->id]);

        $this->actingAs($owner)
            ->patch(route('bookings.customers.archive', [
                'current_team' => $team->slug,
                'customer' => $foreign->id,
            ]))
            ->assertNotFound();
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
