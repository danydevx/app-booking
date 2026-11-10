<?php

namespace Modules\Bookings\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateEmailTemplateRequest extends StoreEmailTemplateRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $template = $this->route('template');

        $rules['name'] = [
            'required',
            'string',
            'max:80',
            Rule::unique('email_templates', 'name')
                ->where(fn ($query) => $query->where('team_id', (int) $this->user()?->current_team_id))
                ->ignore($template),
        ];

        return $rules;
    }
}
