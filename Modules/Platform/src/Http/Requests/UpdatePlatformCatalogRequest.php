<?php

namespace Modules\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePlatformCatalogRequest extends FormRequest
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
            'event_types' => ['required', 'array', 'min:1'],
            'event_types.*.id' => ['nullable', 'integer', 'distinct', 'exists:listing_event_types,id'],
            'event_types.*.name' => ['required', 'string', 'max:80'],
            'event_types.*.description' => ['nullable', 'string', 'max:255'],
            'event_types.*.icon' => ['nullable', 'string', 'max:50'],
            'event_types.*.is_active' => ['required', 'boolean'],
            'event_types.*.sort_order' => ['required', 'integer', 'min:0', 'max:999'],

            'characteristics' => ['required', 'array'],
            'characteristics.*.id' => [
                'required',
                'integer',
                'distinct',
                'exists:listing_characteristics,id',
            ],
            'characteristics.*.description' => ['nullable', 'string', 'max:255'],
            'characteristics.*.icon' => ['nullable', 'string', 'max:50'],
            'characteristics.*.is_featured' => ['required', 'boolean'],
            'characteristics.*.sort_order' => ['required', 'integer', 'min:0', 'max:999'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $names = collect((array) $this->input('event_types', []))
                ->map(fn (mixed $eventType): string => is_array($eventType)
                    ? mb_strtolower(trim((string) ($eventType['name'] ?? '')))
                    : '')
                ->filter();

            if ($names->count() !== $names->unique()->count()) {
                $validator->errors()->add(
                    'event_types',
                    'Los nombres de los tipos de evento no pueden repetirse.',
                );
            }
        });
    }
}
