<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cotización {{ $quote->folio ?? ('COT-'.str_pad((string) $quote->id, 6, '0', STR_PAD_LEFT)) }}</title>
    <style>
        @page { margin: 36px 40px 54px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.45; }
        h1, h2, h3, p { margin: 0; }
        .muted { color: #6b7280; }
        .header { width: 100%; border-bottom: 2px solid #c90345; padding-bottom: 18px; }
        .header-table { width: 100%; }
        .header-table td { vertical-align: top; }
        .brand { width: 62%; padding-right: 24px; }
        .logo { max-width: 150px; max-height: 64px; margin-bottom: 10px; }
        .brand-name { font-size: 18px; font-weight: bold; color: #111827; margin-bottom: 4px; }
        .brand-line { color: #4b5563; margin-top: 2px; }
        .document { width: 38%; text-align: right; }
        .document-title { color: #c90345; font-size: 20px; font-weight: bold; letter-spacing: 1px; }
        .document-number { margin-top: 5px; font-size: 13px; font-weight: bold; }
        .document-meta { color: #4b5563; margin-top: 3px; }
        .section { margin-top: 20px; }
        .section-title { color: #c90345; font-size: 9px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; margin-bottom: 7px; }
        .facts { width: 100%; }
        .facts td { width: 50%; vertical-align: top; padding-right: 22px; }
        .facts p { margin-bottom: 3px; }
        .label { color: #6b7280; font-size: 9px; }
        .value { font-weight: bold; color: #111827; }
        .items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .items thead { display: table-header-group; }
        .items th { background: #fafafa; color: #6b7280; font-size: 8px; letter-spacing: .7px; text-align: left; text-transform: uppercase; padding: 7px 6px; border-bottom: 1px solid #e5e7eb; }
        .items td { padding: 8px 6px; border-bottom: 1px solid #f0f0f0; vertical-align: top; }
        .items tr { page-break-inside: avoid; }
        .items .number { text-align: right; white-space: nowrap; }
        .item-details { color: #6b7280; font-size: 9px; margin-top: 2px; }
        .totals-wrap { width: 100%; margin-top: 14px; }
        .totals { width: 46%; margin-left: 54%; }
        .totals-row { width: 100%; }
        .totals-row td { padding: 4px 0; }
        .totals-row .amount { text-align: right; white-space: nowrap; }
        .totals-row.total td { border-top: 2px solid #111827; font-size: 14px; font-weight: bold; padding-top: 9px; }
        .totals-row.total .amount { color: #c90345; }
        .notes { margin-top: 22px; page-break-inside: avoid; }
        .notes-block { border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 12px; margin-top: 6px; color: #4b5563; }
        .footer { position: fixed; bottom: -38px; left: 0; right: 0; border-top: 1px solid #e5e7eb; padding-top: 7px; color: #9ca3af; font-size: 8px; text-align: center; }
        .legal { margin-top: 24px; color: #9ca3af; font-size: 8px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="brand">
                    @if ($logoDataUri)
                        <img src="{{ $logoDataUri }}" class="logo" alt="Logotipo de {{ $issuer['name'] }}">
                    @endif
                    <div class="brand-name">{{ $issuer['name'] }}</div>
                    @if (! empty($issuer['address']))
                        <div class="brand-line">{{ $issuer['address'] }}</div>
                    @endif
                    @if (! empty($issuer['location']))
                        <div class="brand-line">{{ $issuer['location'] }}</div>
                    @endif
                    @if (! empty($issuer['phone']))
                        <div class="brand-line">{{ $issuer['phone'] }}</div>
                    @endif
                    @if (! empty($issuer['email']))
                        <div class="brand-line">{{ $issuer['email'] }}</div>
                    @endif
                    @if (! empty($issuer['website']))
                        <div class="brand-line">{{ $issuer['website'] }}</div>
                    @endif
                </td>
                <td class="document">
                    <div class="document-title">COTIZACIÓN</div>
                    <div class="document-number">{{ $quote->folio ?? ('COT-'.str_pad((string) $quote->id, 6, '0', STR_PAD_LEFT)) }}</div>
                    <div class="document-meta">Emitida: {{ $quote->issued_at?->format('d/m/Y') ?? now()->format('d/m/Y') }}</div>
                    <div class="document-meta">Vigencia: {{ $quote->expires_at?->format('d/m/Y') ?? 'Por definir' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <table class="facts">
            <tr>
                <td>
                    <div class="section-title">Cliente</div>
                    <p class="value">{{ $customer['name'] }}</p>
                    @if (! empty($customer['email']))
                        <p>{{ $customer['email'] }}</p>
                    @endif
                    @if (! empty($customer['phone']))
                        <p>{{ $customer['phone'] }}</p>
                    @endif
                </td>
                <td>
                    <div class="section-title">Evento</div>
                    <p><span class="label">Tipo:</span> <span class="value">{{ $quote->event_type ?: 'Por definir' }}</span></p>
                    <p><span class="label">Fecha:</span> <span class="value">{{ $quote->start_date->format('d/m/Y') }}@if ($quote->end_date->toDateString() !== $quote->start_date->toDateString()) al {{ $quote->end_date->format('d/m/Y') }}@endif</span></p>
                    @if ($quote->start_time)
                        <p><span class="label">Horario:</span> <span class="value">{{ substr((string) $quote->start_time, 0, 5) }} - {{ $quote->end_time ? substr((string) $quote->end_time, 0, 5) : 'Por definir' }}</span></p>
                    @endif
                    @if ($quote->guests)
                        <p><span class="label">Invitados:</span> <span class="value">{{ $quote->guests }}</span></p>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Conceptos</div>
        <table class="items">
            <thead>
                <tr>
                    <th style="width: 30%;">Concepto</th>
                    <th style="width: 28%;">Descripción</th>
                    <th style="width: 12%;">Cantidad</th>
                    <th class="number" style="width: 15%;">Precio unitario</th>
                    <th class="number" style="width: 15%;">Importe</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td><strong>{{ $item['description'] }}</strong></td>
                        <td>
                            @if (! empty($item['details']))
                                <div class="item-details">{{ $item['details'] }}</div>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td>{{ $item['quantity'] }} {{ $item['unit_label'] }}</td>
                        <td class="number">${{ number_format($item['unit_price_cents'] / 100, 2) }}</td>
                        <td class="number">${{ number_format($item['amount_cents'] / 100, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="totals-wrap">
        <table class="totals">
            <tr class="totals-row">
                <td>Subtotal</td>
                <td class="amount">${{ number_format($totals['subtotal_cents'] / 100, 2) }} {{ $quote->currency }}</td>
            </tr>
            @if ($totals['discount_cents'] > 0)
                <tr class="totals-row">
                    <td>Descuento</td>
                    <td class="amount">-${{ number_format($totals['discount_cents'] / 100, 2) }}</td>
                </tr>
                <tr class="totals-row">
                    <td>Base para IVA</td>
                    <td class="amount">${{ number_format($totals['tax_base_cents'] / 100, 2) }}</td>
                </tr>
            @endif
            @if ($quote->tax_mode === 'added')
                <tr class="totals-row">
                    <td>IVA {{ number_format($quote->tax_rate_bps / 100, 2) }}%</td>
                    <td class="amount">${{ number_format($totals['tax_cents'] / 100, 2) }}</td>
                </tr>
            @elseif ($quote->tax_mode === 'included')
                <tr class="totals-row">
                    <td>IVA incluido ({{ number_format($quote->tax_rate_bps / 100, 2) }}%)</td>
                    <td class="amount">${{ number_format($totals['tax_cents'] / 100, 2) }}</td>
                </tr>
            @endif
            <tr class="totals-row total">
                <td>Total</td>
                <td class="amount">${{ number_format($totals['total_cents'] / 100, 2) }} {{ $quote->currency }}</td>
            </tr>
        </table>
    </div>

    @if ($quote->terms || $quote->notes)
        <div class="notes">
            @if ($quote->terms)
                <div class="section-title">Condiciones comerciales</div>
                <div class="notes-block">{!! nl2br(e($quote->terms)) !!}</div>
            @endif
            @if ($quote->notes)
                <div class="section-title" style="margin-top: 10px;">Observaciones</div>
                <div class="notes-block">{!! nl2br(e($quote->notes)) !!}</div>
            @endif
        </div>
    @endif

    <div class="footer">
        <div>{{ $issuer['name'] }}@if (! empty($issuer['phone'])) · {{ $issuer['phone'] }}@endif @if (! empty($issuer['email'])) · {{ $issuer['email'] }}@endif</div>
        <div style="margin-top: 3px;">Documento de cotización comercial. No constituye factura fiscal ni comprobante CFDI.</div>
    </div>
</body>
</html>
