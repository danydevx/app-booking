<?php

namespace Modules\Listings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Listings\Enums\ListingServicePriceUnit;
use Modules\Listings\Enums\ListingServicePricingMode;
use Modules\Listings\Enums\ListingServiceProvider;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingService;
use Modules\Listings\Support\CommercialServiceCatalog;

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
                Rule::in(CommercialServiceCatalog::keys()),
                Rule::unique('listing_services', 'service_key')
                    ->where(fn ($query) => $query->where('listing_id', $listingId))
                    ->ignore($service instanceof ListingService ? $service->id : null),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'pricing_mode' => [
                'required',
                'string',
                Rule::in(array_column(ListingServicePricingMode::options(), 'value')),
            ],
            'provider' => [
                'required',
                'string',
                Rule::in(array_column(ListingServiceProvider::options(), 'value')),
            ],
            'price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:10000000',
                'required_if:pricing_mode,'.ListingServicePricingMode::Additional->value,
            ],
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
        $pricingMode = $this->input('pricing_mode');

        if (! is_string($pricingMode) || $pricingMode === '') {
            $pricingMode = $this->boolean('is_included')
                ? ListingServicePricingMode::Included->value
                : (filled($this->input('price'))
                    ? ListingServicePricingMode::Additional->value
                    : ListingServicePricingMode::Quote->value);
        }

        $provider = $this->input('provider');

        $this->merge([
            'pricing_mode' => $pricingMode,
            'provider' => is_string($provider) && $provider !== ''
                ? $provider
                : ListingServiceProvider::Own->value,
            'active' => $this->boolean('active'),
        ]);
    }
}
