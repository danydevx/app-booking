<?php

namespace Modules\Bookings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Bookings\Models\Customer;

class UpdateCustomerRequest extends FormRequest
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
        $customer = $this->route('customer');
        $teamId = $customer instanceof Customer
            ? $customer->team_id
            : (int) $this->user()?->current_team_id;

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('customers', 'email')
                    ->where(fn ($query) => $query->where('team_id', $teamId))
                    ->ignore($customer instanceof Customer ? $customer->id : null),
            ],
            'phone' => ['nullable', 'string', 'max:40'],
            'whatsapp_phone' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('team_members', 'user_id')
                    ->where(fn ($query) => $query->where('team_id', $teamId)),
            ],
        ];
    }
}
