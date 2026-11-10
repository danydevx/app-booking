<?php

namespace Modules\Bookings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MergeCustomerRequest extends FormRequest
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
            'target_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where(
                    fn ($query) => $query->where('team_id', (int) $this->user()?->current_team_id),
                ),
            ],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }
}
