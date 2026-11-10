<?php

namespace Modules\Listings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Listings\Models\ListingPromotion;

class SaveListingPromotionRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'promotional_price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'previous_price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'starts_at' => ['required', 'date_format:Y-m-d'],
            'ends_at' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_at'],
            'applies_on' => ['nullable', 'array'],
            'applies_on.*' => ['string', Rule::in(array_keys(ListingPromotion::WEEKDAYS))],
            'conditions' => ['nullable', 'string', 'max:2000'],
            'active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'applies_on' => array_values(array_unique((array) $this->input('applies_on', []))),
            'active' => $this->boolean('active'),
        ]);
    }
}
