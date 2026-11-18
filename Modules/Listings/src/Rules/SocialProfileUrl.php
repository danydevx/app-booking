<?php

namespace Modules\Listings\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Listings\Support\SocialProfileLink;

class SocialProfileUrl implements ValidationRule
{
    public function __construct(
        private readonly string $network,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $url = is_string($value) ? SocialProfileLink::normalize($value) : null;

        if ($url === null || ! SocialProfileLink::isValid($this->network, $url)) {
            $label = SocialProfileLink::NETWORKS[$this->network] ?? $this->network;
            $hint = SocialProfileLink::hint($this->network);

            $fail($hint === ''
                ? "Ingresa un enlace válido de {$label}."
                : "Ingresa un enlace válido de {$label} ({$hint}).");
        }
    }
}
