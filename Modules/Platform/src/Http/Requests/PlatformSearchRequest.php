<?php

namespace Modules\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlatformSearchRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:120'],
        ];
    }
}
