<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Modules\Listings\Models\Listing;
use SimpleXMLElement;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $cacheKey = 'seo:sitemap:'.md5((string) config('app.url'));

        $xml = Cache::remember($cacheKey, now()->addHour(), function (): string {
            $urls = [
                [
                    'loc' => route('home'),
                    'changefreq' => 'daily',
                    'priority' => '1.0',
                    'lastmod' => null,
                ],
                [
                    'loc' => route('public.listings.index'),
                    'changefreq' => 'daily',
                    'priority' => '0.9',
                    'lastmod' => null,
                ],
                [
                    'loc' => route('public.owners'),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                    'lastmod' => null,
                ],
                [
                    'loc' => route('public.guided-search.index'),
                    'changefreq' => 'weekly',
                    'priority' => '0.6',
                    'lastmod' => null,
                ],
            ];

            Listing::query()
                ->publiclyVisible()
                ->orderByDesc('updated_at')
                ->get(['slug', 'updated_at'])
                ->each(function (Listing $listing) use (&$urls): void {
                    $urls[] = [
                        'loc' => route('public.listings.show', ['listing' => $listing->slug]),
                        'changefreq' => 'weekly',
                        'priority' => '0.8',
                        'lastmod' => $listing->updated_at?->toAtomString(),
                    ];
                });

            $document = new SimpleXMLElement(
                '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>',
            );

            foreach ($urls as $url) {
                $node = $document->addChild('url');
                $node->addChild('loc', htmlspecialchars($url['loc'], ENT_XML1));

                if ($url['lastmod'] !== null) {
                    $node->addChild('lastmod', $url['lastmod']);
                }

                $node->addChild('changefreq', $url['changefreq']);
                $node->addChild('priority', $url['priority']);
            }

            return (string) $document->asXML();
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
