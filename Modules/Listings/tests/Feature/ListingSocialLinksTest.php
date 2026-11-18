<?php

namespace Modules\Listings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Listings\Models\Listing;
use Modules\Listings\Support\SocialProfileLink;
use Tests\TestCase;

class ListingSocialLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_save_social_links_without_scheme(): void
    {
        [$owner, $team] = $this->ownerWithTeam();

        $this->actingAs($owner)
            ->post(route('listings.store', ['current_team' => $team->slug]), [
                'name' => 'Terraza Social',
                'facebook_url' => 'facebook.com/miterraza',
                'instagram_url' => 'instagram.com/miterraza',
                'tiktok_url' => 'tiktok.com/@miterraza',
                'google_maps_url' => 'maps.app.goo.gl/abc123',
            ])
            ->assertSessionHasNoErrors();

        $listing = Listing::query()->where('team_id', $team->id)->firstOrFail();

        $this->assertSame('https://facebook.com/miterraza', $listing->facebook_url);
        $this->assertSame('https://instagram.com/miterraza', $listing->instagram_url);
        $this->assertSame('https://tiktok.com/@miterraza', $listing->tiktok_url);
        $this->assertSame('https://maps.app.goo.gl/abc123', $listing->google_maps_url);
    }

    public function test_owner_form_exposes_the_saved_social_links(): void
    {
        [$owner, $team] = $this->ownerWithTeam();

        $listing = Listing::factory()->forTeam($team)->create([
            'facebook_url' => 'https://facebook.com/terraza',
            'instagram_url' => 'https://instagram.com/terraza',
            'tiktok_url' => null,
            'google_maps_url' => 'https://maps.app.goo.gl/abc',
        ]);

        $this->actingAs($owner)
            ->get(route('listings.edit', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('listing.facebook_url', 'https://facebook.com/terraza')
                ->where('listing.instagram_url', 'https://instagram.com/terraza')
                ->where('listing.tiktok_url', null)
                ->where('listing.google_maps_url', 'https://maps.app.goo.gl/abc'));
    }

    public function test_social_links_must_match_their_network(): void
    {
        [$owner, $team] = $this->ownerWithTeam();

        $this->actingAs($owner)
            ->post(route('listings.store', ['current_team' => $team->slug]), [
                'name' => 'Terraza Inválida',
                'facebook_url' => 'https://instagram.com/algo',
                'instagram_url' => 'https://tiktok.com/@algo',
                'tiktok_url' => 'https://facebook.com/algo',
                'google_maps_url' => 'https://facebook.com/maps',
            ])
            ->assertSessionHasErrors([
                'facebook_url',
                'instagram_url',
                'tiktok_url',
                'google_maps_url',
            ]);

        $this->assertSame(0, Listing::query()->count());

        $this->actingAs($owner)
            ->post(route('listings.store', ['current_team' => $team->slug]), [
                'name' => 'Terraza Phishing',
                'facebook_url' => 'https://facebook.com.evil.test/pagina',
            ])
            ->assertSessionHasErrors('facebook_url');

        $this->assertSame(0, Listing::query()->count());
    }

    public function test_social_link_allowlist_accepts_known_profiles_only(): void
    {
        foreach ([
            'https://facebook.com/terraza',
            'https://www.facebook.com/terraza',
            'https://fb.com/terraza',
            'https://m.facebook.com/terraza',
        ] as $url) {
            $this->assertTrue(SocialProfileLink::isValid('facebook', $url), $url);
        }

        $this->assertTrue(SocialProfileLink::isValid('instagram', 'https://instagram.com/terraza'));
        $this->assertTrue(SocialProfileLink::isValid('tiktok', 'https://www.tiktok.com/@terraza'));
        $this->assertTrue(SocialProfileLink::isValid('tiktok', 'https://vm.tiktok.com/abc'));

        foreach ([
            'https://google.com/maps/place/Terraza',
            'https://www.google.com/maps/place/Terraza',
            'https://google.com.mx/maps/place/Terraza',
            'https://maps.google.com/?q=terraza',
            'https://maps.app.goo.gl/abc',
            'https://goo.gl/maps/abc',
        ] as $url) {
            $this->assertTrue(SocialProfileLink::isValid('google_maps', $url), $url);
        }

        $this->assertFalse(SocialProfileLink::isValid('google_maps', 'https://google.com/search?q=terraza'));
        $this->assertFalse(SocialProfileLink::isValid('facebook', 'https://facebook.com.evil.test/x'));
        $this->assertFalse(SocialProfileLink::isValid('instagram', 'https://instagram.com.evil.test/x'));
        $this->assertFalse(SocialProfileLink::isValid('tiktok', 'https://tiktok.com.evil.test/x'));
        $this->assertFalse(SocialProfileLink::isValid('facebook', 'javascript:alert(1)'));
    }

    public function test_public_detail_exposes_social_links_in_order(): void
    {
        [, $team] = $this->ownerWithTeam();

        $listing = Listing::factory()->forTeam($team)->published()->create([
            'facebook_url' => 'https://facebook.com/terraza',
            'instagram_url' => null,
            'tiktok_url' => 'https://tiktok.com/@terraza',
            'google_maps_url' => 'https://maps.app.goo.gl/abc',
        ]);

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Public/Show')
                ->has('listing.social_links', 3)
                ->where('listing.social_links.0.key', 'facebook')
                ->where('listing.social_links.0.label', 'Facebook')
                ->where('listing.social_links.0.url', 'https://facebook.com/terraza')
                ->where('listing.social_links.1.key', 'tiktok')
                ->where('listing.social_links.2.key', 'google_maps')
                ->where('listing.social_links.2.url', 'https://maps.app.goo.gl/abc'));
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
