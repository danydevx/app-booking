<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /app/',
            'Disallow: /settings/',
            'Disallow: /dashboard',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /forgot-password',
            'Disallow: /reset-password',
            'Disallow: /email/verify',
            'Disallow: /passkeys/',
            'Disallow: /cotizaciones/',
            'Disallow: /reserva/',
            'Disallow: /baja/',
            'Disallow: /*/reclamar',
            'Disallow: /*/reportar',
            'Disallow: /buscar/resultados',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
