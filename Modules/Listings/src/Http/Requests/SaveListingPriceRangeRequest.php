<?php

namespace Modules\Listings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveListingPriceRangeRequest extends FormRequest
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
            'starts_at' => ['required', 'date_format:Y-m-d'],
            'ends_at' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_at'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'daily_pricing' => ['nullable', 'array:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'daily_pricing.*.hours' => ['nullable', 'integer', 'min:1', 'max:24'],
            'daily_pricing.*.entry_time' => ['nullable', 'date_format:H:i'],
            'daily_pricing.*.exit_time' => ['nullable', 'date_format:H:i'],
            'daily_pricing.*.price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'included_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
            'included_people' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'extra_hour' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'extra_person' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'deposit_required' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'conditions' => ['nullable', 'string', 'max:2000'],
            'active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['active' => $this->boolean('active')]);
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $pricing = $this->input('daily_pricing');
                $hasDailyPrice = false;

                if (is_array($pricing)) {
                    foreach ($pricing as $row) {
                        if (is_array($row) && filled($row['price'] ?? null)) {
                            $hasDailyPrice = true;
                            break;
                        }
                    }
                }

                if (! $hasDailyPrice && ! filled($this->input('price'))) {
                    $validator->errors()->add(
                        'daily_pricing',
                        'Define el precio de al menos un día.',
                    );
                }
            },
        ];
    }
}
