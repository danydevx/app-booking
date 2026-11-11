<?php

namespace Modules\Listings\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Listings\Support\MexicoLocationCatalog;
use Modules\Listings\Support\TerraceDetailCatalog;
use Modules\Listings\Support\TerraceServiceCatalog;

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

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'address' => ['nullable', 'string', 'max:255'],
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
            'state' => ['nullable', 'string', Rule::in(MexicoLocationCatalog::stateValues())],
            'country' => ['nullable', 'string', Rule::in(MexicoLocationCatalog::countryValues())],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'services' => ['nullable', 'array'],
            'services.*' => ['string', 'distinct', Rule::in($services)],
            'details' => ['nullable', $detailKeyRule, 'max:20'],
            'details.*' => ['nullable', 'string', 'max:255'],
            'rules' => ['nullable', 'string', 'max:2000'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'whatsapp_phone' => ['nullable', 'string', 'max:40'],
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

            if (
                $state !== ''
                && ! in_array($country, MexicoLocationCatalog::countryValues(), true)
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
