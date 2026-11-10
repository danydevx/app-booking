<?php

namespace Modules\Bookings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Bookings\Models\Customer;

class SendCustomerEmailRequest extends FormRequest
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
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:20000'],
            'template_id' => [
                'nullable',
                'integer',
                Rule::exists('email_templates', 'id')
                    ->where(fn ($query) => $query->where('team_id', $teamId)),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['subject', 'body'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }
}
