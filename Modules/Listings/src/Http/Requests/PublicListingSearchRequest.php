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
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'service' => ['nullable', 'string', Rule::in($services)],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:1', "max:{$maxRadius}"],
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
        });
    }
}
