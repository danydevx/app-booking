<?php

namespace Modules\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Platform\Models\GeoMunicipality;
use Modules\Platform\Models\GeoState;

class UpdateGeographicCoverageRequest extends FormRequest
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
            'states' => ['required', 'array', 'min:1'],
            'states.*.id' => ['required', 'integer', 'distinct', 'exists:geo_states,id'],
            'states.*.enabled' => ['required', 'boolean'],
            'states.*.all_municipalities' => ['required', 'boolean'],
            'states.*.municipality_ids' => ['nullable', 'array'],
            'states.*.municipality_ids.*' => ['integer', 'distinct', 'exists:geo_municipalities,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('states', []) as $index => $payload) {
                if (! is_array($payload) || ! isset($payload['id'])) {
                    continue;
                }

                $state = GeoState::query()->find((int) $payload['id']);

                if ($state === null) {
                    continue;
                }

                $enabled = (bool) ($payload['enabled'] ?? false);
                $all = (bool) ($payload['all_municipalities'] ?? false);
                $ids = array_map('intval', $payload['municipality_ids'] ?? []);

                if ($enabled && ! $all && $ids === []) {
                    $validator->errors()->add(
                        "states.{$index}.municipality_ids",
                        'Selecciona al menos un municipio para la cobertura parcial.',
                    );

                    continue;
                }

                if ($ids !== []) {
                    $owned = GeoMunicipality::query()
                        ->where('geo_state_id', $state->id)
                        ->whereIn('id', $ids)
                        ->count();

                    if ($owned !== count($ids)) {
                        $validator->errors()->add(
                            "states.{$index}.municipality_ids",
                            'Todos los municipios deben pertenecer al estado seleccionado.',
                        );
                    }
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $states = $this->input('states');

        if (! is_array($states)) {
            return;
        }

        $this->merge([
            'states' => array_map(function (mixed $state): mixed {
                if (! is_array($state)) {
                    return $state;
                }

                $state['municipality_ids'] = array_values(
                    array_map('intval', (array) ($state['municipality_ids'] ?? [])),
                );

                return $state;
            }, $states),
        ]);
    }
}
