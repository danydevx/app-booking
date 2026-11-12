<?php

namespace Modules\Bookings\Support;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Bookings\Models\CustomerQuote;
use Modules\Bookings\Models\CustomerQuoteItem;

final class QuotationPdfRenderer
{
    private const UNIT_LABELS = [
        'hour' => 'hora(s)',
        'day' => 'día(s)',
        'event' => 'evento',
        'service' => 'servicio',
        'piece' => 'pieza(s)',
        'person' => 'persona(s)',
    ];

    public function stream(CustomerQuote $quote): string
    {
        $quote->loadMissing(['items', 'customer', 'listing.team', 'team']);

        $issuer = $this->issuer($quote);
        $customer = $this->customer($quote);
        $items = $quote->items
            ->map(fn (CustomerQuoteItem $item): array => [
                'description' => $item->description,
                'details' => $item->details,
                'quantity' => $item->quantity,
                'unit_label' => self::UNIT_LABELS[$item->unit] ?? $item->unit,
                'unit_price_cents' => $item->unit_price_cents,
                'amount_cents' => $item->amount_cents,
            ])
            ->values()
            ->all();

        $calculator = app(QuoteCalculator::class);
        $calculation = $calculator->calculate(
            $quote->items->pluck('amount_cents')->all(),
            (string) $quote->tax_mode,
            (int) $quote->tax_rate_bps,
            (string) $quote->discount_type,
            (int) $quote->discount_value,
        );

        // Para cotizaciones emitidas los conceptos y precios están congelados,
        // así que recalcular produce exactamente el mismo resultado. Para
        // borradores es la única fuente de verdad (nunca se confía en el navegador).
        $totals = $quote->items->isNotEmpty()
            ? $calculation
            : [
                'subtotal_cents' => $quote->subtotal_cents,
                'tax_base_cents' => max(0, $quote->subtotal_cents - $quote->discount_cents),
                'discount_cents' => $quote->discount_cents,
                'tax_cents' => $quote->tax_cents,
                'total_cents' => $quote->totalCents(),
            ];

        $html = view('bookings::quotes.pdf', [
            'quote' => $quote,
            'issuer' => $issuer,
            'customer' => $customer,
            'items' => $items,
            'totals' => $totals,
            'logoDataUri' => $this->logoDataUri(
                $issuer['logo_path'] ?? null,
                (string) ($issuer['logo_disk'] ?? 'public'),
            ),
        ])->render();

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $pdf = new Dompdf($options);
        $pdf->setPaper((string) config('bookings.quotes.paper', 'letter'), 'portrait');
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->render();

        return (string) $pdf->output();
    }

    public function store(CustomerQuote $quote, string $pdf): string
    {
        $disk = (string) config('bookings.quotes.pdf_disk', 'local');
        $path = 'quotes/'.$quote->team_id.'/'.$quote->id.'/quote-'.($quote->folio ?? $quote->id).'.pdf';

        Storage::disk($disk)->put($path, $pdf);

        return $path;
    }

    /**
     * Copia el logo actual a almacenamiento privado para que el documento
     * emitido siga siendo reproducible si la terraza cambia o elimina su logo.
     *
     * @param  array<string, mixed>  $issuer
     * @return array<string, mixed>
     */
    public function preserveLogo(CustomerQuote $quote, array $issuer): array
    {
        $path = $issuer['logo_path'] ?? null;

        if (blank($path)) {
            return [...$issuer, 'logo_disk' => null];
        }

        $public = Storage::disk('public');

        if (! $public->exists((string) $path)) {
            return [...$issuer, 'logo_disk' => null];
        }

        [$mime, $contents] = $this->normalizeImage(
            $public->get((string) $path),
            (string) ($public->mimeType((string) $path) ?: 'image/png'),
        );

        $extension = match ($mime) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/gif' => 'gif',
            default => pathinfo((string) $path, PATHINFO_EXTENSION) ?: 'png',
        };

        $stored = 'quotes/'.$quote->team_id.'/'.$quote->id.'/logo-'.Str::random(12).'.'.$extension;

        Storage::disk((string) config('bookings.quotes.pdf_disk', 'local'))
            ->put($stored, $contents);

        return [
            ...$issuer,
            'logo_path' => $stored,
            'logo_disk' => (string) config('bookings.quotes.pdf_disk', 'local'),
        ];
    }

    /**
     * @return array{name: string, address: string|null, location: string|null, phone: string|null, email: string|null, website: string|null, logo_path: string|null, logo_disk: string|null}
     */
    public function issuer(CustomerQuote $quote): array
    {
        if (is_array($quote->issuer_snapshot)) {
            return [
                'name' => (string) ($quote->issuer_snapshot['name'] ?? ''),
                'address' => $quote->issuer_snapshot['address'] ?? null,
                'location' => $quote->issuer_snapshot['location'] ?? null,
                'phone' => $quote->issuer_snapshot['phone'] ?? null,
                'email' => $quote->issuer_snapshot['email'] ?? null,
                'website' => $quote->issuer_snapshot['website'] ?? null,
                'logo_path' => $quote->issuer_snapshot['logo_path'] ?? null,
                'logo_disk' => $quote->issuer_snapshot['logo_disk'] ?? 'public',
            ];
        }

        $listing = $quote->listing;
        $team = $listing->team;

        return [
            'name' => $listing->name,
            'address' => $listing->address,
            'location' => collect([$listing->city, $listing->state])->filter()->implode(', ') ?: null,
            'phone' => $listing->contact_phone ?? $team?->contact_phone,
            'email' => $listing->contact_email ?? $team?->contact_email,
            'website' => null,
            'logo_path' => $listing->logo_path,
            'logo_disk' => 'public',
        ];
    }

    /**
     * @return array{name: string, email: string|null, phone: string|null, notes: string|null}
     */
    public function customer(CustomerQuote $quote): array
    {
        if (is_array($quote->customer_snapshot)) {
            return [
                'name' => (string) ($quote->customer_snapshot['name'] ?? ''),
                'email' => $quote->customer_snapshot['email'] ?? null,
                'phone' => $quote->customer_snapshot['phone'] ?? null,
                'notes' => $quote->customer_snapshot['notes'] ?? null,
            ];
        }

        return [
            'name' => $quote->customer->name,
            'email' => $quote->customer->email,
            'phone' => $quote->customer->phone,
            'notes' => null,
        ];
    }

    private function logoDataUri(?string $path, string $disk): ?string
    {
        if (blank($path)) {
            return null;
        }

        $storage = Storage::disk($disk);

        if (! $storage->exists($path)) {
            return null;
        }

        [$mime, $contents] = $this->normalizeImage(
            $storage->get($path),
            (string) ($storage->mimeType($path) ?: 'image/png'),
        );

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    /**
     * Dompdf no admite WebP en todas sus versiones; se convierte a PNG para
     * garantizar que el logo aparezca en el documento.
     *
     * @return array{0: string, 1: string}
     */
    private function normalizeImage(string $contents, string $mime): array
    {
        if ($mime !== 'image/webp' || ! function_exists('imagecreatefromstring')) {
            return [$mime, $contents];
        }

        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            return [$mime, $contents];
        }

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return ['image/png', $png];
    }
}
