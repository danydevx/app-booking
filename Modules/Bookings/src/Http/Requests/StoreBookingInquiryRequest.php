<?php

namespace Modules\Bookings\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Bookings\Support\DateRange;
use Modules\Listings\Models\Listing;

class StoreBookingInquiryRequest extends FormRequest
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
            'phone' => ['required', 'string', 'max:40'],
            'whatsapp_phone' => ['nullable', 'string', 'max:40'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d'],
            'event_type' => ['nullable', 'string', 'max:80'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'message' => ['nullable', 'string', 'max:2000'],
            'source_detail' => ['nullable', 'string', 'max:500'],
            'utm_source' => ['nullable', 'string', 'max:500'],
            'utm_medium' => ['nullable', 'string', 'max:500'],
            'utm_campaign' => ['nullable', 'string', 'max:500'],
            'utm_term' => ['nullable', 'string', 'max:500'],
            'utm_content' => ['nullable', 'string', 'max:500'],
            'referrer' => ['nullable', 'string', 'max:2000'],
            'consent' => ['accepted'],
            'website' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['start_date', 'end_date'])) {
                return;
            }

            $listing = $this->route('listing');
            $timezone = $listing instanceof Listing
                ? $listing->timezone
                : (string) config('app.timezone');
            $today = CarbonImmutable::now($timezone)->toDateString();

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
        });
    }
}
