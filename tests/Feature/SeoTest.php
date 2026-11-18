<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Listings\Enums\ListingMediaType;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_robots_txt_allows_public_pages_and_blocks_private_ones(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $contents = (string) $response->getContent();

        $this->assertStringContainsString('User-agent: *', $contents);
        $this->assertStringContainsString('Allow: /', $contents);
        $this->assertStringContainsString('Disallow: /app/', $contents);
        $this->assertStringContainsString('Disallow: /settings/', $contents);
        $this->assertStringContainsString('Disallow: /cotizaciones/', $contents);
        $this->assertStringContainsString('Disallow: /*/reclamar', $contents);
        $this->assertStringContainsString('Sitemap: '.url('/sitemap.xml'), $contents);
    }

    public function test_sitemap_lists_public_pages_and_visible_listings_only(): void
    {
        $owner = User::factory()->create();
        $team = $owner->currentTeam;

        $published = Listing::factory()->forTeam($team)->published()->create([
            'name' => 'Terraza Indexable',
            'slug' => 'terraza-indexable',
        ]);

        $draft = Listing::factory()->forTeam($team)->create([
            'name' => 'Terraza Borrador',
            'slug' => 'terraza-borrador',
        ]);

        $suspended = Listing::factory()->forTeam($team)->suspended()->create([
            'name' => 'Terraza Suspendida',
            'slug' => 'terraza-suspendida',
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = (string) $response->getContent();

        $this->assertStringContainsString('<urlset', $xml);
        $this->assertStringContainsString(route('home'), $xml);
        $this->assertStringContainsString(route('public.listings.index'), $xml);
        $this->assertStringContainsString(route('public.owners'), $xml);
        $this->assertStringContainsString(
            route('public.listings.show', ['listing' => $published->slug]),
            $xml,
        );
        $this->assertStringNotContainsString($draft->slug, $xml);
        $this->assertStringNotContainsString($suspended->slug, $xml);
    }

    public function test_public_pages_expose_seo_metadata(): void
    {
        $owner = User::factory()->create();
        $team = $owner->currentTeam;

        $listing = Listing::factory()->forTeam($team)->published()->create([
            'name' => 'Terraza Jardín SEO',
            'slug' => 'terraza-jardin-seo',
            'description' => 'Terraza para eventos sociales con jardín y alberca.',
            'latitude' => 20.6736,
            'longitude' => -103.4055,
            'facebook_url' => 'https://facebook.com/terraza-seo',
            'instagram_url' => 'https://instagram.com/terraza-seo',
        ]);

        $listing->media()->create([
            'type' => ListingMediaType::Image,
            'disk' => 'public',
            'path' => 'listings/seo/cover.png',
            'mime_type' => 'image/png',
            'size_bytes' => 1024,
            'width' => 1200,
            'height' => 800,
            'duration_seconds' => null,
            'alt_text' => 'Portada',
            'sort_order' => 1,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('seo.canonical', route('home'))
                ->where('seo.robots', 'index, follow')
                ->has('seo.site_name')
                ->has('seo.description'));

        $this->get(route('public.listings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('seo.canonical', route('public.listings.index'))
                ->where('seo.robots', 'index, follow'));

        $this->get(route('public.owners'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('seo.canonical', route('public.owners')));

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Public/Show')
                ->where('seo.canonical', route('public.listings.show', ['listing' => $listing->slug]))
                ->where('seo.description', 'Terraza para eventos sociales con jardín y alberca.')
                ->where('seo.robots', 'index, follow')
                ->where('seo.json_ld.@type', 'EventVenue')
                ->where('seo.json_ld.name', 'Terraza Jardín SEO')
                ->where('seo.json_ld.address.addressLocality', 'Guadalajara')
                ->where('seo.json_ld.geo.latitude', 20.6736)
                ->where('seo.json_ld.sameAs.0', 'https://facebook.com/terraza-seo')
                ->where('seo.json_ld.sameAs.1', 'https://instagram.com/terraza-seo')
                ->where('seo.json_ld.maximumAttendeeCapacity', $listing->capacity)
                ->has('seo.image'));
    }

    public function test_private_pages_send_a_noindex_header_and_public_pages_do_not(): void
    {
        $owner = User::factory()->create();
        $team = $owner->currentTeam;

        $listing = Listing::factory()->forTeam($team)->published()->create([
            'slug' => 'terraza-header-seo',
        ]);

        $login = $this->get(route('login'));
        $this->assertStringContainsString('noindex', (string) $login->headers->get('X-Robots-Tag'));

        $dashboard = $this->actingAs($owner)->get(route('dashboard', ['current_team' => $team->slug]));
        $this->assertStringContainsString('noindex', (string) $dashboard->headers->get('X-Robots-Tag'));

        $claim = $this->get(route('public.claims.create', ['listing' => $listing->slug]));
        $this->assertStringContainsString('noindex', (string) $claim->headers->get('X-Robots-Tag'));

        $public = $this->get(route('public.listings.show', ['listing' => $listing->slug]));
        $this->assertNull($public->headers->get('X-Robots-Tag'));
    }
}
