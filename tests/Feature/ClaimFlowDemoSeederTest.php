<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Database\Seeders\ClaimFlowDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\Listings\Enums\ListingStatus;
use Modules\Listings\Models\Listing;
use Modules\Platform\Enums\ListingClaimStatus;
use Modules\Platform\Models\ListingClaim;
use Tests\TestCase;

class ClaimFlowDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Http::fake([
            'placehold.co/*' => Http::response(
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII='),
                200,
                ['Content-Type' => 'image/png'],
            ),
        ]);
    }

    public function test_it_seeds_two_claimable_terraces_and_one_pending_claim(): void
    {
        $this->seed(ClaimFlowDemoSeeder::class);
        $this->seed(ClaimFlowDemoSeeder::class);

        $directory = Team::query()->where('is_directory', true)->firstOrFail();

        $listings = Listing::query()
            ->whereIn('external_ref', ['demo-claim-uno', 'demo-claim-dos'])
            ->with(['team', 'eventTypes'])
            ->get();

        $this->assertCount(2, $listings);
        $this->assertSame(2, Listing::query()->where('team_id', $directory->id)->count());

        foreach ($listings as $listing) {
            $this->assertSame($directory->id, $listing->team_id);
            $this->assertSame(ListingStatus::Published, $listing->status);
            $this->assertNull($listing->claimed_at);
            $this->assertTrue($listing->isClaimable());
            $this->assertSame(1, $listing->media()->count());
            $this->assertNotEmpty($listing->eventTypes);
        }

        $owners = User::query()
            ->whereIn('email', ['dueno.uno@terrazas.test', 'dueno.dos@terrazas.test'])
            ->get();

        $this->assertCount(2, $owners);

        foreach ($owners as $owner) {
            $this->assertNotNull($owner->email_verified_at);
            $this->assertNotNull($owner->currentTeam);
            $this->assertFalse($owner->currentTeam->isDirectory());
        }

        $this->assertSame(1, ListingClaim::query()->count());

        $claim = ListingClaim::query()->with(['listing', 'claimant', 'targetTeam'])->firstOrFail();

        $this->assertSame(ListingClaimStatus::Pending, $claim->status);
        $this->assertSame('demo-claim-uno', $claim->listing->external_ref);
        $this->assertSame('dueno.uno@terrazas.test', $claim->claimant->email);
        $this->assertSame($claim->claimant->currentTeam?->id, $claim->target_team_id);
    }
}
