<?php

namespace Modules\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlatformPlanRequest extends FormRequest
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
        $limitKeys = array_keys((array) config('billing.limit_labels', []));
        $featureKeys = array_keys((array) config('billing.feature_labels', []));

        return [
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
            'stripe_price_id' => ['nullable', 'string', 'max:255'],
            'interval' => ['required', 'string', Rule::in(['month', 'year'])],
            'active' => ['boolean'],
            'limits' => $limitKeys === [] ? ['nullable', 'array'] : ['required', 'array:'.implode(',', $limitKeys)],
            'limits.*' => ['integer', 'min:0', 'max:1000000'],
            'features' => $featureKeys === [] ? ['nullable', 'array'] : ['required', 'array:'.implode(',', $featureKeys)],
            'features.*' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'active' => $this->boolean('active'),
        ]);
    }
}
