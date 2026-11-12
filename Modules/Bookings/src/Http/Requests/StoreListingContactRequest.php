<?php

namespace Modules\Bookings\Http\Requests;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Listings\Models\Listing;

class StoreListingContactRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => [
                'nullable',
                'string',
                'max:40',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || trim($value) === '') {
                        return;
                    }

                    $digits = preg_replace('/\D+/', '', $value);

                    if (! is_string($digits) || strlen($digits) < 10 || strlen($digits) > 13) {
                        $fail('Ingresa un teléfono válido de 10 dígitos (puedes incluir +52).');
                    }
                },
            ],
            'message' => ['required', 'string', 'max:2000'],
            'event_type' => ['nullable', 'string', 'max:80'],
            'date_mode' => ['required', 'string', Rule::in(['exact', 'approximate'])],
            'event_date' => ['required_if:date_mode,exact', 'nullable', 'date_format:Y-m-d'],
            'event_month' => [
                'required_if:date_mode,approximate',
                'nullable',
                'string',
                'regex:/^\d{4}-(0[1-9]|1[0-2])$/',
            ],
            'guest_range' => [
                'required',
                'string',
                Rule::in(array_keys((array) config('bookings.contact_guest_ranges', []))),
            ],
            'website' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['event_date', 'event_month', 'date_mode'])) {
                return;
            }

            $listing = $this->route('listing');
            $timezone = $listing instanceof Listing
                ? $listing->timezone
                : (string) config('app.timezone');
            $today = CarbonImmutable::now($timezone);

            if ($this->input('date_mode') === 'exact') {
                $date = (string) $this->input('event_date');

                if ($date !== '' && $date < $today->toDateString()) {
                    $validator->errors()->add('event_date', 'La fecha del evento no puede ser pasada.');
                }

                return;
            }

            $month = (string) $this->input('event_month');

            if ($month !== '' && $month < $today->format('Y-m')) {
                $validator->errors()->add('event_month', 'El mes del evento no puede ser pasado.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? trim((string) $this->input('name')) : $this->input('name'),
            'email' => is_string($this->input('email')) ? trim((string) $this->input('email')) : $this->input('email'),
            'phone' => is_string($this->input('phone')) && trim((string) $this->input('phone')) !== ''
                ? trim((string) $this->input('phone'))
                : null,
            'event_type' => is_string($this->input('event_type')) && trim((string) $this->input('event_type')) !== ''
                ? trim((string) $this->input('event_type'))
                : null,
            'event_date' => is_string($this->input('event_date')) && trim((string) $this->input('event_date')) !== ''
                ? trim((string) $this->input('event_date'))
                : null,
            'event_month' => is_string($this->input('event_month')) && trim((string) $this->input('event_month')) !== ''
                ? trim((string) $this->input('event_month'))
                : null,
        ]);
    }
}
