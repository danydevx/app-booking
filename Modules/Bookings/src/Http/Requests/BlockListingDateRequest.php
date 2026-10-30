<?php

namespace Modules\Bookings\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Listings\Models\Listing;

class BlockListingDateRequest extends FormRequest
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
            'date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('date')) {
                return;
            }

            $listing = $this->route('listing');
            $timezone = $listing instanceof Listing
                ? $listing->timezone
                : (string) config('app.timezone');
            $today = CarbonImmutable::now($timezone)->toDateString();

            if ((string) $this->input('date') < $today) {
                $validator->errors()->add('date', 'No puedes bloquear una fecha pasada.');
            }
        });
    }
}
