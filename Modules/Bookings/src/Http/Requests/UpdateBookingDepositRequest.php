<?php

namespace Modules\Bookings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingDepositRequest extends FormRequest
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
            'amount' => ['sometimes', 'numeric', 'min:0.01', 'max:1000000'],
            'amount_cents' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', Rule::in(array_keys(config('bookings.deposit_statuses', [])))],
            'method' => ['sometimes', Rule::in(array_keys(config('bookings.deposit_methods', [])))],
            'reference' => ['nullable', 'string', 'max:120'],
            'received_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_numeric($this->input('amount'))) {
            $this->merge([
                'amount_cents' => (int) round(((float) $this->input('amount')) * 100),
            ]);
        }
    }
}
