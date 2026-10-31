<?php

namespace Modules\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Platform\Enums\ReportResolution;
use Modules\Platform\Enums\ReportStatus;

class UpdateListingReportRequest extends FormRequest
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
            'status' => [
                'required',
                Rule::enum(ReportStatus::class),
                Rule::notIn([ReportStatus::Open->value]),
            ],
            'resolution_action' => [
                'nullable',
                Rule::enum(ReportResolution::class),
                'required_if:status,'.ReportStatus::Resolved->value.','.ReportStatus::Dismissed->value,
            ],
            'resolution_note' => [
                'nullable',
                'string',
                'max:2000',
                'required_if:status,'.ReportStatus::Resolved->value.','.ReportStatus::Dismissed->value,
            ],
        ];
    }
}
