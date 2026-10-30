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
        $type = $this->input('type') === ListingMediaType::Video->value
            ? ListingMediaType::Video
            : ListingMediaType::Image;

        $mimes = config("listings.media.{$type->value}_mimes", []);
        $mimetypes = config("listings.media.{$type->value}_mimetypes", []);
        $maxKb = (int) config(
            $type === ListingMediaType::Image
                ? 'listings.media.max_image_kb'
                : 'listings.media.max_video_kb',
            5120,
        );

        return [
            'type' => ['required', Rule::in([
                ListingMediaType::Image->value,
                ListingMediaType::Video->value,
            ])],
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
