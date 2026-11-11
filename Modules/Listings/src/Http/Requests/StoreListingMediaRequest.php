<?php

namespace Modules\Listings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Listings\Enums\ListingMediaType;

class StoreListingMediaRequest extends FormRequest
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
        $mimes = config('listings.media.image_mimes', []);
        $mimetypes = config('listings.media.image_mimetypes', []);
        $maxKb = (int) config('listings.media.max_image_kb', 5120);

        return [
            'type' => ['nullable', Rule::in([ListingMediaType::Image->value])],
            'alt_text' => ['nullable', 'string', 'max:150'],
            'file' => [
                'required',
                'file',
                "max:{$maxKb}",
                'mimes:'.implode(',', $mimes),
                'mimetypes:'.implode(',', $mimetypes),
            ],
        ];
    }
}
