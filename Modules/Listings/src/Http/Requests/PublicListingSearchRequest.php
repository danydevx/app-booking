<?php

namespace Modules\Listings\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Listings\Support\MexicoLocationCatalog;
use Modules\Listings\Support\TerraceServiceCatalog;

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
        $maxRadius = (int) config('listings.search.max_radius_km', 100);

        return [
            'q' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', Rule::in(MexicoLocationCatalog::stateValues())],
            'city' => [
                'nullable',
                'string',
                'max:120',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || $value === '') {
                        return;
                    }

                    $state = (string) $this->input('state');

                    if (
                        $state === '' ||
                        ! in_array($state, MexicoLocationCatalog::stateValues(), true)
                    ) {
                        return;
                    }

                    if (! in_array($value, MexicoLocationCatalog::municipalities($state), true)) {
                        $fail('Selecciona un municipio o alcaldía válido para el estado.');
                    }
                },
            ],
            'postal_code' => ['nullable', 'string', 'max:10', 'regex:/^\d{4,6}$/'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'service' => ['nullable', 'string', Rule::in($services)],
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
