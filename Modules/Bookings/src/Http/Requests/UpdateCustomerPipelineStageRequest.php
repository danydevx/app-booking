<?php

namespace Modules\Bookings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Bookings\Enums\CustomerPipelineStage;

class UpdateCustomerPipelineStageRequest extends FormRequest
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
            'stage' => [
                'required',
                'string',
                Rule::in(array_column(CustomerPipelineStage::options(), 'value')),
            ],
        ];
    }
}
