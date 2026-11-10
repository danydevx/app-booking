<?php

namespace Modules\Listings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Listings\Support\TerraceServiceCatalog;

class SaveListingPackageRequest extends FormRequest
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
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'duration_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'included_people' => ['required', 'integer', 'min:1', 'max:100000'],
            'included_amenities' => ['nullable', 'array'],
            'included_amenities.*' => ['string', Rule::in(TerraceServiceCatalog::amenityKeys())],
            'included_services' => ['nullable', 'array'],
            'included_services.*' => ['string', Rule::in(TerraceServiceCatalog::commercialServiceKeys())],
            'conditions' => ['nullable', 'string', 'max:2000'],
            'active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'included_amenities' => array_values(array_unique((array) $this->input('included_amenities', []))),
            'included_services' => array_values(array_unique((array) $this->input('included_services', []))),
            'active' => $this->boolean('active'),
        ]);
    }
}
