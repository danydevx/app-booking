<?php

namespace Modules\Listings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Listings\Enums\ListingServicePriceUnit;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingService;
use Modules\Listings\Support\TerraceServiceCatalog;

class SaveListingServiceRequest extends FormRequest
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
        $listing = $this->route('listing');
        $service = $this->route('service');
        $listingId = $listing instanceof Listing ? $listing->id : null;

        return [
            'service_key' => [
                'required',
                'string',
                Rule::in(array_keys(TerraceServiceCatalog::all())),
                Rule::unique('listing_services', 'service_key')
                    ->where(fn ($query) => $query->where('listing_id', $listingId))
                    ->ignore($service instanceof ListingService ? $service->id : null),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_included' => ['boolean'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'price_unit' => [
                'required',
                'string',
                Rule::in(array_column(ListingServicePriceUnit::options(), 'value')),
            ],
            'active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_included' => $this->boolean('is_included'),
            'active' => $this->boolean('active'),
        ]);
    }
}
