<?php

namespace Modules\Listings\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Listings\Support\ListingPublicCatalog;
use Modules\Listings\Support\TerraceServiceCatalog;
use Modules\Platform\Support\GeographicCoverageService;

class PublicListingSearchRequest extends FormRequest
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
        $services = array_keys(TerraceServiceCatalog::all());
        $eventSlugs = ListingPublicCatalog::activeEventSlugs();
        $maxRadius = (int) config('listings.search.max_radius_km', 100);
        $coverage = app(GeographicCoverageService::class);

        return [
            'q' => ['nullable', 'string', 'max:120'],
            'state' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, Closure $fail) use ($coverage): void {
                    if (! is_string($value) || $value === '') {
                        return;
                    }

                    if (! $coverage->isStateEnabled($value)) {
                        $fail('Selecciona un estado habilitado.');
                    }
                },
            ],
            'city' => [
                'nullable',
                'string',
                'max:120',
                function (string $attribute, mixed $value, Closure $fail) use ($coverage): void {
                    if (! is_string($value) || $value === '') {
                        return;
                    }

                    $state = (string) $this->input('state');

                    if ($state === '') {
                        $fail('Selecciona un estado antes del municipio.');

                        return;
                    }

                    if (! $coverage->isMunicipalityEnabled($state, $value)) {
                        $fail('Selecciona un municipio habilitado para el estado.');
                    }
                },
            ],
            'postal_code' => ['nullable', 'string', 'max:10', 'regex:/^\d{4,6}$/'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'service' => ['nullable', 'string', Rule::in($services)],
            'features' => ['nullable', 'array', 'max:10'],
            'features.*' => ['string', 'distinct', Rule::in($services)],
            'event' => ['nullable', 'string', Rule::in($eventSlugs)],
            'events' => ['nullable', 'array', 'max:10'],
            'events.*' => ['string', 'distinct', Rule::in($eventSlugs)],
            'price_min' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'price_max' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:1', "max:{$maxRadius}"],
            'north' => ['nullable', 'numeric', 'between:-90,90'],
            'south' => ['nullable', 'numeric', 'between:-90,90'],
            'east' => ['nullable', 'numeric', 'between:-180,180'],
            'west' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasLat = $this->filled('lat');
            $hasLng = $this->filled('lng');

            if ($hasLat !== $hasLng) {
                $validator->errors()->add('lat', 'Indica latitud y longitud juntas para buscar por radio.');
            }

            $bounds = ['north', 'south', 'east', 'west'];
            $filledBounds = array_values(array_filter(
                $bounds,
                fn (string $key): bool => $this->filled($key),
            ));

            if ($filledBounds !== [] && count($filledBounds) !== count($bounds)) {
                $validator->errors()->add(
                    'north',
                    'Indica los cuatro límites del mapa para buscar en esta zona.',
                );

                return;
            }

            if (count($filledBounds) === count($bounds)) {
                $north = (float) $this->input('north');
                $south = (float) $this->input('south');
                $east = (float) $this->input('east');
                $west = (float) $this->input('west');

                if ($north <= $south) {
                    $validator->errors()->add('north', 'El límite norte debe ser mayor que el sur.');
                }

                if ($east <= $west) {
                    $validator->errors()->add('east', 'El límite este debe ser mayor que el oeste.');
                }
            }

            $min = $this->input('price_min');
            $max = $this->input('price_max');

            if (
                is_numeric($min)
                && is_numeric($max)
                && (int) $min > (int) $max
            ) {
                $validator->errors()->add('price_max', 'El precio máximo debe ser mayor o igual al mínimo.');
            }

            $featureValues = array_values(array_filter(
                (array) $this->input('features', []),
                fn (mixed $feature): bool => is_string($feature) && $feature !== '',
            ));
            $service = $this->input('service');

            if (is_string($service) && $service !== '') {
                $featureValues[] = $service;
            }

            if (
                ! $validator->errors()->has('features')
                && count(array_unique($featureValues)) > 10
            ) {
                $validator->errors()->add(
                    'features',
                    'Puedes seleccionar hasta 10 características.',
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $query = $this->input('q');

        if (is_string($query)) {
            $this->merge(['q' => trim(preg_replace('/\s+/', ' ', $query) ?? '')]);
        }
    }
}
