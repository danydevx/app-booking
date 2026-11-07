<?php

namespace Modules\Listings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
        $services = array_keys(TerraceServiceCatalog::all());
        $detailFields = TerraceDetailCatalog::all();
        $detailKeys = array_keys($detailFields);
        $detailKeyRule = $detailKeys === []
            ? 'array'
            : 'array:'.implode(',', $detailKeys);

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'services' => ['nullable', 'array'],
            'services.*' => ['string', Rule::in($services)],
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

    protected function prepareForValidation(): void
    {
        $details = collect((array) $this->input('details', []))
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->all();

        $this->merge([
            'services' => array_values(array_filter((array) $this->input('services', []))),
            'details' => $details === [] ? null : $details,
        ]);
    }
}
