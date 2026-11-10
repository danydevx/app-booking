<?php

namespace Modules\Listings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveListingPricingRequest extends FormRequest
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
            'price_from' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'price_weekday' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'price_weekend' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'included_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
            'included_people' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'extra_hour' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'extra_person' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'deposit_required' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'price_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('price_notes'))) {
            $this->merge(['price_notes' => trim($this->input('price_notes'))]);
        }
    }
}
