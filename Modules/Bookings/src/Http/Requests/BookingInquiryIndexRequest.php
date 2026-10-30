<?php

namespace Modules\Bookings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookingInquiryIndexRequest extends FormRequest
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
            'status' => ['nullable', Rule::in(array_keys(config('bookings.inquiry_statuses', [])))],
            'listing' => ['nullable', 'string', 'max:160'],
            'search' => ['nullable', 'string', 'max:120'],
        ];
    }
}
