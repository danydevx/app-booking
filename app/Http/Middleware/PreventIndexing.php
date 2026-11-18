<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds X-Robots-Tag to private or token-based pages as a second line of defense
 * on top of robots.txt.
 */
class PreventIndexing
{
    /**
     * @var array<int, string>
     */
    private const PREFIXES = [
        'app',
        'settings',
        'dashboard',
        'login',
        'register',
        'forgot-password',
        'reset-password',
        'email',
        'passkeys',
        'cotizaciones',
        'reserva',
        'baja',
        'buscar/resultados',
    ];

    /**
     * @var array<int, string>
     */
    private const SUFFIXES = [
        '/reclamar',
        '/reportar',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldPreventIndexing($request->path())) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }

        return $response;
    }

    private function shouldPreventIndexing(string $path): bool
    {
        $path = trim($path, '/');

        foreach (self::PREFIXES as $prefix) {
            if ($path === $prefix || Str::startsWith($path, $prefix.'/')) {
                return true;
            }
        }

        foreach (self::SUFFIXES as $suffix) {
            if (Str::endsWith($path, $suffix)) {
                return true;
            }
        }

        return false;
    }
}
