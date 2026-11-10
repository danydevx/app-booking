<?php

namespace Modules\Bookings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Bookings\Enums\CustomerTaskPriority;

class StoreCustomerTaskRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'due_at' => ['nullable', 'date'],
            'priority' => [
                'required',
                'string',
                Rule::in(array_column(CustomerTaskPriority::options(), 'value')),
            ],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('team_members', 'user_id')->where(
                    fn ($query) => $query->where('team_id', (int) $this->user()?->current_team_id),
                ),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => is_string($this->input('title')) ? trim($this->input('title')) : $this->input('title'),
            'notes' => is_string($this->input('notes')) ? trim($this->input('notes')) : $this->input('notes'),
        ]);
    }
}
