<?php

namespace Modules\Bookings\Http\Requests;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Bookings\Models\CustomerQuote;
use Modules\Bookings\Support\QuoteCalculator;

class SaveQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $quote = $this->route('quote');

        if ($quote instanceof CustomerQuote) {
            abort_unless($quote->team_id === $this->teamId(), 404);
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $teamId = $this->teamId();

        return [
            'intent' => ['nullable', Rule::in(['draft', 'preview', 'issue'])],
            'listing_id' => [
                'required',
                'integer',
                Rule::exists('listings', 'id')->where('team_id', $teamId),
            ],
            'customer_id' => [
                'nullable',
                'required_without:new_customer.name',
                'integer',
                Rule::exists('customers', 'id')->where('team_id', $teamId),
            ],
            'new_customer' => ['nullable', 'array'],
            'new_customer.name' => [
                'nullable',
                'required_without:customer_id',
                'string',
                'max:120',
            ],
            'new_customer.email' => [
                'nullable',
                'required_without:customer_id',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->where('team_id', $teamId),
            ],
            'new_customer.phone' => ['nullable', 'string', 'max:40'],
            'new_customer.notes' => ['nullable', 'string', 'max:5000'],
            'converted_inquiry_id' => [
                'nullable',
                'integer',
                Rule::exists('booking_inquiries', 'id')->where('team_id', $teamId),
            ],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'rental_mode' => ['required', Rule::in(['hour', 'day', 'fixed'])],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'billing_quantity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'event_type' => ['nullable', 'string', 'max:80'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'tax_mode' => ['required', Rule::in(['none', 'added', 'included'])],
            'discount_type' => ['required', Rule::in(['none', 'fixed', 'percent'])],
            'discount_value' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'deposit_cents' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'expires_at' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.type' => [
                'required',
                Rule::in(['rental', 'service', 'package', 'product', 'custom']),
            ],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.details' => ['nullable', 'string', 'max:1000'],
            'items.*.unit' => [
                'required',
                Rule::in(['hour', 'day', 'event', 'service', 'piece', 'person']),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.unit_price_cents' => [
                'required',
                'integer',
                'min:0',
                'max:1000000000',
            ],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $items = collect((array) $this->input('items', []));
            $amounts = $items
                ->map(fn (mixed $item): int => is_array($item)
                    ? ((int) ($item['quantity'] ?? 0)) * ((int) ($item['unit_price_cents'] ?? 0))
                    : 0)
                ->all();

            $discountType = (string) $this->input('discount_type', 'none');
            $discountValue = (int) $this->input('discount_value', 0);

            if ($discountType === 'percent' && $discountValue > 10000) {
                $validator->errors()->add(
                    'discount_value',
                    'El descuento porcentual no puede superar el 100%.',
                );

                return;
            }

            $totals = app(QuoteCalculator::class)->calculate(
                $amounts,
                (string) $this->input('tax_mode', 'none'),
                (int) config('bookings.quotes.tax_rate_bps', 1600),
                $discountType,
                $discountValue,
            );

            if ($discountType === 'fixed' && $discountValue > $totals['subtotal_cents']) {
                $validator->errors()->add(
                    'discount_value',
                    'El descuento no puede superar el importe de los conceptos.',
                );
            }

            $deposit = (int) $this->input('deposit_cents', 0);

            if ($deposit > $totals['total_cents']) {
                $validator->errors()->add(
                    'deposit_cents',
                    'El anticipo no puede superar el total de la cotización.',
                );
            }

            if ($this->input('rental_mode') === 'hour') {
                if (blank($this->input('start_time')) || blank($this->input('end_time'))) {
                    $validator->errors()->add(
                        'start_time',
                        'Indica la hora inicial y final para la renta por horas.',
                    );
                }
            }
        });
    }

    private function teamId(): int
    {
        $slug = (string) $this->route('current_team');

        if ($slug !== '') {
            return (int) Team::query()->where('slug', $slug)->value('id');
        }

        return (int) $this->user()->current_team_id;
    }

    protected function prepareForValidation(): void
    {
        foreach (['event_type', 'notes', 'terms'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim((string) $this->input($field))]);
            }
        }

        if (is_string($this->input('new_customer.name'))) {
            $this->merge(['new_customer.name' => trim((string) $this->input('new_customer.name'))]);
        }

        foreach ((array) $this->input('items', []) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            foreach (['description', 'details'] as $field) {
                if (is_string($item[$field] ?? null)) {
                    $item[$field] = trim($item[$field]);
                }
            }

            $this->merge(['items.'.$index => $item]);
        }
    }
}
