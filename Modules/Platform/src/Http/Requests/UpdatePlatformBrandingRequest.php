<?php

namespace Modules\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['legal_terms_url', 'legal_privacy_url'] as $field) {
            $value = $this->input($field);

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $value = trim($value);

            $looksLikeDomain = str_contains($value, '.') && preg_match('/\s/', $value) !== 1;

            if ($looksLikeDomain && ! preg_match('#^https?://#i', $value)) {
                $this->merge([$field => 'https://'.$value]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:80'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'from_address' => ['required', 'email', 'max:255'],
            'legal_terms_url' => ['nullable', 'url', 'max:255'],
            'legal_privacy_url' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }
}
