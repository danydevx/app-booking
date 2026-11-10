<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Cashier\Subscription;
use Modules\Bookings\Models\Customer;
use Tests\TestCase;

class CustomerImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_pro_owner_can_import_customers_from_csv(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);
        Customer::factory()->create([
            'team_id' => $team->id,
            'email' => 'existente@example.test',
        ]);

        $file = UploadedFile::fake()->createWithContent('clientes.csv', implode("\n", [
            'Nombre,Correo,Teléfono,WhatsApp,Notas',
            'Cliente Uno,uno@example.test,5555555555,5215555555555,Cliente importado',
            'Cliente Duplicado,existente@example.test,5555555555,,Duplicado',
            'Cliente Inválido,no-es-correo,5555555555,,Inválido',
        ]));

        $response = $this->actingAs($owner)
            ->post(route('bookings.customers.import.store', [
                'current_team' => $team->slug,
            ]), ['file' => $file]);

        $response
            ->assertRedirect(route('bookings.customers.import', ['current_team' => $team->slug]))
            ->assertSessionHas('import_result');

        $result = session('import_result');

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['skipped']);
        $this->assertCount(1, $result['errors']);
        $this->assertDatabaseHas('customers', [
            'team_id' => $team->id,
            'email' => 'uno@example.test',
            'name' => 'Cliente Uno',
        ]);
        $this->assertDatabaseHas('customer_activities', [
            'body' => 'Cliente importado desde CSV.',
        ]);
    }

    public function test_free_plan_cannot_import_customers(): void
    {
        [$owner, $team] = $this->ownerWithTeam();

        $file = UploadedFile::fake()->createWithContent(
            'clientes.csv',
            "Nombre,Correo\nCliente,cliente@example.test\n",
        );

        $this->actingAs($owner)
            ->post(route('bookings.customers.import.store', [
                'current_team' => $team->slug,
            ]), ['file' => $file])
            ->assertRedirect(route('billing.index', ['current_team' => $team->slug]));

        $this->assertDatabaseCount('customers', 0);
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
