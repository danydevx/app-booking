<?php

namespace Modules\Listings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'price' => ['required', 'numeric', 'min:0', 'max:10000000'],
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
}
