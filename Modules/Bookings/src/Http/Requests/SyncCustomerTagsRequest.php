<?php

namespace Modules\Bookings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncCustomerTagsRequest extends FormRequest
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
            'tag_ids' => ['array', 'max:20'],
            'tag_ids.*' => [
                'integer',
                Rule::exists('customer_tags', 'id')->where(
                    fn ($query) => $query->where('team_id', (int) $this->user()?->current_team_id),
                ),
            ],
        ];
    }
}
