<?php

namespace Modules\Bookings\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Bookings\Support\DateRange;
use Modules\Listings\Models\Listing;

class BlockListingDateRangeRequest extends FormRequest
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
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['nullable', 'string', 'max:255'],
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
            $start = (string) $this->input('start_date');
            $end = (string) $this->input('end_date');

            if ($start < $today) {
                $validator->errors()->add('start_date', 'No puedes bloquear fechas pasadas.');
            }

            if ($end < $start) {
                $validator->errors()->add('end_date', 'La fecha final debe ser igual o posterior al inicio.');
            }

            $maxDays = (int) config('bookings.max_block_days', 365);

            if (DateRange::days($start, $end) > $maxDays) {
                $validator->errors()->add('end_date', "El rango máximo es de {$maxDays} días.");
            }
        });
    }
}
