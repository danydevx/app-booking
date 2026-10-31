<?php

namespace Tests\Feature\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class TeamBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_branding_and_upload_a_logo(): void
    {
        Storage::fake('public');

        [$owner, $team] = $this->ownerWithTeam();

        $this->actingAs($owner)
            ->patch(route('teams.branding.update', ['team' => $team->slug]), [
                'name' => 'Terrazas del Centro',
                'timezone' => 'America/Monterrey',
                'contact_email' => 'hola@centro.test',
                'contact_phone' => '+52 81 1234 5678',
                'whatsapp_phone' => '+52 81 8765 4321',
                'logo' => UploadedFile::fake()->image('logo.png', 300, 300),
            ])
            ->assertRedirect();

        $team->refresh();

        $this->assertSame('Terrazas del Centro', $team->name);
        $this->assertSame('America/Monterrey', $team->timezone);
        $this->assertSame('hola@centro.test', $team->contact_email);
        $this->assertSame('+52 81 1234 5678', $team->contact_phone);
        $this->assertSame('+52 81 8765 4321', $team->whatsapp_phone);
        $this->assertNotNull($team->logo_path);
        Storage::disk('public')->assertExists($team->logo_path);
    }

    public function test_logo_must_be_a_supported_image(): void
    {
        Storage::fake('public');

        [$owner, $team] = $this->ownerWithTeam();

        $this->actingAs($owner)
            ->patch(route('teams.branding.update', ['team' => $team->slug]), [
                'name' => $team->name,
                'timezone' => $team->timezone,
                'logo' => UploadedFile::fake()->create('logo.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors('logo');

        $this->assertNull($team->fresh()->logo_path);
    }

    public function test_members_and_other_accounts_cannot_update_branding(): void
    {
        [$owner, $team] = $this->ownerWithTeam();

        $member = User::factory()->create();
        $member->teams()->attach($team, ['role' => TeamRole::Member->value]);
        $member->switchTeam($team);

        $this->actingAs($member)
            ->patch(route('teams.branding.update', ['team' => $team->slug]), [
                'name' => 'Cambio no autorizado',
                'timezone' => $team->timezone,
            ])
            ->assertForbidden();

        $otherOwner = User::factory()->create();

        $this->actingAs($otherOwner)
            ->patch(route('teams.branding.update', ['team' => $team->slug]), [
                'name' => 'Cambio no autorizado',
                'timezone' => $team->timezone,
            ])
            ->assertForbidden();

        $this->assertSame($team->name, $team->fresh()->name);
    }

    public function test_public_listing_uses_account_branding_and_whatsapp_fallback(): void
    {
        Storage::fake('public');

        [$owner, $team] = $this->ownerWithTeam();

        $this->actingAs($owner)
            ->patch(route('teams.branding.update', ['team' => $team->slug]), [
                'name' => 'Anfitriones del Valle',
                'timezone' => $team->timezone,
                'contact_email' => 'reservas@valle.test',
                'contact_phone' => '+52 55 1111 2222',
                'whatsapp_phone' => '+52 55 3333 4444',
                'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
            ])
            ->assertRedirect();

        $listing = Listing::factory()->forTeam($team)->published()->create([
            'contact_email' => null,
            'contact_phone' => null,
            'whatsapp_phone' => null,
        ]);

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('listing.account.name', 'Anfitriones del Valle')
                ->where('listing.account.contact_email', 'reservas@valle.test')
                ->where('listing.account.contact_phone', '+52 55 1111 2222')
                ->where(
                    'listing.account.whatsapp_url',
                    'https://wa.me/525533334444?text='.rawurlencode("Hola, me interesa {$listing->name}."),
                )
                ->where('listing.account.logo_url', fn ($url) => is_string($url) && $url !== ''));
    }

    /**
     * @return array{0: User, 1: Team}
     */
    private function ownerWithTeam(): array
    {
        $owner = User::factory()->create();

        return [$owner, $owner->currentTeam];
    }
}
