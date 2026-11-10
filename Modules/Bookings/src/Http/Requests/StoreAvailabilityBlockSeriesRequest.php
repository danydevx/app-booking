<?php

namespace Modules\Bookings\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Bookings\Enums\AvailabilityRecurrence;
use Modules\Bookings\Support\DateRange;
use Modules\Listings\Models\Listing;

class StoreAvailabilityBlockSeriesRequest extends FormRequest
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
            'reason' => ['nullable', 'string', 'max:255'],
            'recurrence' => [
                'required',
                'string',
                Rule::in(array_column(AvailabilityRecurrence::options(), 'value')),
            ],
            'weekdays' => ['array'],
            'weekdays.*' => ['integer', 'between:1,7'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['required', 'date_format:Y-m-d'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['starts_on', 'ends_on', 'recurrence'])) {
                return;
            }

            $listing = $this->route('listing');
            $timezone = $listing instanceof Listing
                ? $listing->timezone
                : (string) config('app.timezone');

            $today = CarbonImmutable::now($timezone)->toDateString();
            $start = (string) $this->input('starts_on');
            $end = (string) $this->input('ends_on');
            $recurrence = AvailabilityRecurrence::tryFrom((string) $this->input('recurrence'));
            $weekdays = array_filter((array) $this->input('weekdays', []), 'is_numeric');

            if ($start < $today) {
                $validator->errors()->add('starts_on', 'La recurrencia no puede iniciar en el pasado.');
            }

            if ($end < $start) {
                $validator->errors()->add('ends_on', 'La fecha final debe ser posterior al inicio.');
            }

            $horizon = (int) config('bookings.availability_recurrence_horizon_days', 366);

            if (DateRange::days($start, $end) > $horizon) {
                $validator->errors()->add('ends_on', "La recurrencia máxima es de {$horizon} días.");
            }

            if (
                $recurrence !== null
                && $recurrence !== AvailabilityRecurrence::Monthly
                && $weekdays === []
            ) {
                $validator->errors()->add('weekdays', 'Selecciona al menos un día de la semana.');
            }
        });
    }
}
