<?php

namespace Modules\Bookings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmBookingPortalRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'whatsapp_phone' => ['nullable', 'string', 'max:40'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }
}
