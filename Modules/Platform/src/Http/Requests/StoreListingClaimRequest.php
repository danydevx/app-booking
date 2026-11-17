<?php

namespace Modules\Platform\Http\Requests;

use App\Enums\TeamRole;
use App\Models\Team;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Platform\Enums\ListingClaimRole;

class StoreListingClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:120'],
            'role' => ['required', 'string', Rule::in(ListingClaimRole::values())],
            'claimant_name' => ['required', 'string', 'max:120'],
            'claimant_phone' => [
                'required',
                'string',
                'max:40',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $digits = preg_replace('/\D+/', '', (string) $value);

                    if (! is_string($digits) || strlen($digits) < 10 || strlen($digits) > 13) {
                        $fail('Ingresa un teléfono válido de 10 dígitos (puedes incluir +52).');
                    }
                },
            ],
            'whatsapp_phone' => ['nullable', 'string', 'max:40'],
            'target_team_id' => ['nullable', 'integer', Rule::exists('teams', 'id')],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'evidence' => [
                'nullable',
                'file',
                'mimes:'.implode(',', (array) config('listings.claims.evidence_mimes', ['pdf', 'jpg', 'jpeg', 'png', 'webp'])),
                'max:'.(int) config('listings.claims.max_evidence_kb', 10240),
            ],
            'accept_terms' => ['required', 'accepted'],
            'website' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $targetTeamId = $this->input('target_team_id');

            if ($targetTeamId === null || $targetTeamId === '') {
                return;
            }

            $user = $this->user();
            $team = Team::query()->find((int) $targetTeamId);

            if ($team === null || $team->trashed() || $team->isDirectory()) {
                $validator->errors()->add('target_team_id', 'Selecciona una cuenta válida.');

                return;
            }

            if ($user === null || ! $user->belongsToTeam($team)) {
                $validator->errors()->add('target_team_id', 'No perteneces a esa cuenta.');

                return;
            }

            if (! ($user->teamRole($team)?->isAtLeast(TeamRole::Admin) ?? false)) {
                $validator->errors()->add('target_team_id', 'Necesitas ser propietario o administrador de esa cuenta.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'business_name' => is_string($this->input('business_name')) ? trim((string) $this->input('business_name')) : $this->input('business_name'),
            'claimant_name' => is_string($this->input('claimant_name')) ? trim((string) $this->input('claimant_name')) : $this->input('claimant_name'),
            'claimant_phone' => is_string($this->input('claimant_phone')) ? trim((string) $this->input('claimant_phone')) : $this->input('claimant_phone'),
            'whatsapp_phone' => is_string($this->input('whatsapp_phone')) && trim((string) $this->input('whatsapp_phone')) !== ''
                ? trim((string) $this->input('whatsapp_phone'))
                : null,
            'target_team_id' => $this->input('target_team_id') === '' ? null : $this->input('target_team_id'),
            'message' => is_string($this->input('message')) ? trim((string) $this->input('message')) : $this->input('message'),
        ]);
    }
}
