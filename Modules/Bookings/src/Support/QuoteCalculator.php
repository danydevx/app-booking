<?php

namespace Modules\Bookings\Support;

final class QuoteCalculator
{
    /**
     * Calcula los importes de una cotización en centavos.
     *
     * Orden de operaciones (documentado también en docs):
     * 1. Importe de conceptos: suma de cantidad × precio unitario.
     * 2. Descuento: monto fijo o porcentaje sobre el importe anterior.
     * 3. Base gravable: importe de conceptos menos descuento.
     * 4. IVA:
     *    - `none`: no se desglosa.
     *    - `added`: se suma a la base.
     *    - `included`: ya está dentro de la base; se desglosa sin aumentar el total.
     *
     * @param  array<int, int>  $itemAmounts  Importes de cada concepto en centavos.
     * @return array{subtotal_cents: int, tax_base_cents: int, discount_cents: int, tax_cents: int, total_cents: int}
     */
    public function calculate(
        array $itemAmounts,
        string $taxMode,
        int $taxRateBps,
        string $discountType,
        int $discountValue,
    ): array {
        $itemsSubtotal = max(0, array_sum($itemAmounts));

        $discountCents = match ($discountType) {
            'percent' => $this->roundRatio(
                $itemsSubtotal * min(max(0, $discountValue), 10000),
                10000,
            ),
            'fixed' => min(max(0, $discountValue), $itemsSubtotal),
            default => 0,
        };

        $taxBase = max(0, $itemsSubtotal - $discountCents);
        $rate = min(max(0, $taxRateBps), 10000);

        return match ($taxMode) {
            'added' => (function () use ($itemsSubtotal, $discountCents, $taxBase, $rate): array {
                $tax = $this->roundRatio($taxBase * $rate, 10000);

                return [
                    'subtotal_cents' => $itemsSubtotal,
                    'tax_base_cents' => $taxBase,
                    'discount_cents' => $discountCents,
                    'tax_cents' => $tax,
                    'total_cents' => $taxBase + $tax,
                ];
            })(),
            'included' => (function () use ($itemsSubtotal, $discountCents, $taxBase, $rate): array {
                $tax = $rate > 0
                    ? $this->roundRatio($taxBase * $rate, 10000 + $rate)
                    : 0;

                return [
                    'subtotal_cents' => $itemsSubtotal,
                    'tax_base_cents' => $taxBase,
                    'discount_cents' => $discountCents,
                    'tax_cents' => $tax,
                    'total_cents' => $taxBase,
                ];
            })(),
            default => [
                'subtotal_cents' => $itemsSubtotal,
                'tax_base_cents' => $taxBase,
                'discount_cents' => $discountCents,
                'tax_cents' => 0,
                'total_cents' => $taxBase,
            ],
        };
    }

    private function roundRatio(int $value, int $divisor): int
    {
        if ($divisor <= 0) {
            return 0;
        }

        return intdiv($value + intdiv($divisor, 2), $divisor);
    }
}
