<?php

namespace Modules\Bookings\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Support\DateRange;
use Modules\Listings\Models\Listing;

abstract class CustomerQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $customer = $this->route('customer');
        $teamId = $customer instanceof Customer
            ? $customer->team_id
            : (int) $this->user()?->current_team_id;

        return [
            'listing_id' => [
                'required',
                'integer',
                Rule::exists('listings', 'id')
                    ->where(fn ($query) => $query->where('team_id', $teamId)),
            ],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d'],
            'event_type' => ['nullable', 'string', 'max:80'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'subtotal' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'deposit' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'expires_at' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['event_type', 'notes', 'terms'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $customer = $this->route('customer');
            $teamId = $customer instanceof Customer
                ? $customer->team_id
                : (int) $this->user()?->current_team_id;

            $listing = Listing::query()
                ->where('team_id', $teamId)
                ->find($this->input('listing_id'));

            if (! $listing instanceof Listing) {
                $validator->errors()->add('listing_id', 'Selecciona una terraza válida.');

                return;
            }

            if ($validator->errors()->hasAny(['start_date', 'end_date'])) {
                return;
            }

            $today = CarbonImmutable::now($listing->timezone)->toDateString();

            if ((string) $this->input('start_date') < $today) {
                $validator->errors()->add('start_date', 'La fecha de inicio no puede ser pasada.');
            }

            if ((string) $this->input('end_date') < (string) $this->input('start_date')) {
                $validator->errors()->add('end_date', 'La fecha de fin debe ser igual o posterior al inicio.');
            }

            $days = DateRange::days(
                (string) $this->input('start_date'),
                (string) $this->input('end_date'),
            );
            $maxDays = (int) config('bookings.max_stay_days', 30);

            if ($days > $maxDays) {
                $validator->errors()->add('end_date', "La estancia máxima es de {$maxDays} días.");
            }

            $subtotal = (float) $this->input('subtotal', 0);
            $deposit = (float) $this->input('deposit', 0);

            if ($deposit > $subtotal) {
                $validator->errors()->add('deposit', 'El anticipo no puede superar el total.');
            }
        });
    }
}
