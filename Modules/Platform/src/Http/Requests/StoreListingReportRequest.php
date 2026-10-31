<?php

namespace Modules\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Platform\Enums\ReportReason;

class StoreListingReportRequest extends FormRequest
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
            'reason' => ['required', Rule::enum(ReportReason::class)],
            'message' => [
                'nullable',
                'string',
                'max:2000',
                'required_if:reason,'.ReportReason::Other->value,
            ],
            'reporter_email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
