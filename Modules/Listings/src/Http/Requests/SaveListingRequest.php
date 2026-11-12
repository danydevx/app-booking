<?php

namespace Modules\Listings\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Listings\Models\Listing;
use Modules\Listings\Support\TerraceDetailCatalog;
use Modules\Listings\Support\TerraceServiceCatalog;
use Modules\Platform\Support\GeographicCoverageService;

class SaveListingRequest extends FormRequest
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
        $services = array_merge(
            array_keys(TerraceServiceCatalog::all()),
            TerraceServiceCatalog::legacyCommercialKeys(),
        );
        $detailFields = TerraceDetailCatalog::all();
        $detailKeys = array_keys($detailFields);
        $detailKeyRule = $detailKeys === []
            ? 'array'
            : 'array:'.implode(',', $detailKeys);

        $coverage = app(GeographicCoverageService::class);
        $countryValues = array_column($coverage->countries(), 'value');
        $existingListing = $this->route('listing');
        $existingState = $existingListing instanceof Listing
            ? $existingListing->state
            : null;
        $existingCity = $existingListing instanceof Listing
            ? $existingListing->city
            : null;

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => [
                'nullable',
                'string',
                'max:120',
                function (string $attribute, mixed $value, Closure $fail) use ($coverage, $existingState, $existingCity): void {
                    if (! is_string($value) || $value === '') {
                        return;
                    }

                    $state = trim((string) $this->input('state'));

                    if ($state === '') {
                        return;
                    }

                    $unchanged = $existingState !== null
                        && trim((string) $existingState) === $state
                        && trim((string) $existingCity) === trim($value);

                    if ($unchanged) {
                        return;
                    }

                    if (! $coverage->isMunicipalityEnabled($state, $value)) {
                        $fail('Selecciona un municipio dentro de la cobertura habilitada.');
                    }
                },
            ],
            'state' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, Closure $fail) use ($coverage, $existingState): void {
                    if (! is_string($value) || $value === '') {
                        return;
                    }

                    if ($existingState !== null && trim((string) $existingState) === trim($value)) {
                        return;
                    }

                    if (! $coverage->isStateEnabled($value)) {
                        $fail('Selecciona un estado dentro de la cobertura habilitada.');
                    }
                },
            ],
            'country' => ['nullable', 'string', Rule::in($countryValues)],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'services' => ['nullable', 'array'],
            'services.*' => ['string', 'distinct', Rule::in($services)],
            'event_types' => ['nullable', 'array', 'max:20'],
            'event_types.*' => [
                'integer',
                'distinct',
                Rule::exists('listing_event_types', 'id')->where('is_active', true),
            ],
            'details' => ['nullable', $detailKeyRule, 'max:20'],
            'details.*' => ['nullable', 'string', 'max:255'],
            'rules' => ['nullable', 'string', 'max:2000'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'whatsapp_phone' => ['nullable', 'string', 'max:40'],
            'logo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
                'dimensions:max_width=4000,max_height=4000',
            ],
            'remove_logo' => ['nullable', 'boolean'],
        ];

        foreach ($detailFields as $key => $field) {
            if ($field['type'] !== 'select') {
                continue;
            }

            $rules["details.{$key}"] = [
                'nullable',
                'string',
                Rule::in(array_keys((array) ($field['options'] ?? []))),
            ];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $country = trim((string) $this->input('country'));
            $state = trim((string) $this->input('state'));
            $city = trim((string) $this->input('city'));
            $countryValues = array_column(
                app(GeographicCoverageService::class)->countries(),
                'value',
            );

            if (
                $state !== ''
                && ! in_array($country, $countryValues, true)
            ) {
                $validator->errors()->add(
                    'country',
                    'Selecciona un país compatible con el estado.',
                );
            }

            if ($city !== '' && $state === '') {
                $validator->errors()->add(
                    'state',
                    'Selecciona un estado antes del municipio.',
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $details = collect((array) $this->input('details', []))
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->all();

        $this->merge([
            'services' => array_values(array_unique(array_filter(
                (array) $this->input('services', []),
                fn (mixed $service): bool => is_string($service) && $service !== '',
            ))),
            'details' => $details === [] ? null : $details,
        ]);
    }
}
