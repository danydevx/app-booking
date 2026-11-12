<?php

namespace Modules\Listings\Actions;

use Illuminate\Support\Str;
use Modules\Platform\Support\GeographicCoverageService;

final class ResolveSearchLocation
{
    public function __construct(
        private readonly GeographicCoverageService $coverage,
    ) {}

    /**
     * Resolve a free-text location into structured filters.
     *
     * Supported: state, municipality ("city") and postal code. Neighborhoods
     * are not stored in the listing model, so they are intentionally not
     * guessed here.
     *
     * @return array{state?: string, city?: string, postal_code?: string}|null
     */
    public function handle(string $query): ?array
    {
        $query = trim(preg_replace('/\s+/', ' ', $query) ?? '');

        if ($query === '') {
            return null;
        }

        if (preg_match('/^\d{4,6}$/', $query) === 1) {
            return ['postal_code' => $query];
        }

        $parts = array_map('trim', explode(',', $query, 2));
        $cityPart = $parts[0] !== '' ? $parts[0] : null;
        $statePart = $parts[1] ?? null;

        $states = $this->coverage->locations();

        if ($statePart !== null) {
            foreach ($states as $value => $state) {
                if (! $this->matches($statePart, $this->stateCandidates($value, $state['label']))) {
                    continue;
                }

                if ($cityPart === null) {
                    return ['state' => $value];
                }

                $city = $this->findCity($cityPart, $value);

                return $city === null
                    ? null
                    : ['state' => $value, 'city' => $city];
            }

            return null;
        }

        $cityMatches = [];

        foreach ($states as $value => $state) {
            $city = $this->findCity($query, $value);

            if ($city !== null) {
                $cityMatches[] = ['state' => $value, 'city' => $city];
            }
        }

        if (count($cityMatches) === 1) {
            return $cityMatches[0];
        }

        if (count($cityMatches) > 1) {
            $exact = array_values(array_filter(
                $cityMatches,
                fn (array $match): bool => $this->normalize($match['city']) === $this->normalize($query),
            ));

            return count($exact) === 1 ? $exact[0] : null;
        }

        foreach ($states as $value => $state) {
            if ($this->matches($query, $this->stateCandidates($value, $state['label']))) {
                return ['state' => $value];
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function stateCandidates(string $state, string $label): array
    {
        return array_values(array_unique(array_merge(
            [$state, $label],
            $this->coverage->stateValues($state),
        )));
    }

    private function findCity(string $query, string $stateValue): ?string
    {
        $normalized = $this->normalize($query);
        $candidates = [];

        foreach ($this->coverage->locations()[$stateValue]['municipalities'] ?? [] as $city) {
            $normalizedCity = $this->normalize($city);

            if ($normalizedCity === $normalized) {
                return $city;
            }

            if (strlen($normalized) >= 3 && str_starts_with($normalizedCity, $normalized)) {
                $candidates[] = $city;
            }
        }

        return count($candidates) === 1 ? $candidates[0] : null;
    }

    /**
     * @param  array<int, string>  $values
     */
    private function matches(string $input, array $values): bool
    {
        $normalized = $this->normalize($input);

        if ($normalized === '') {
            return false;
        }

        foreach ($values as $value) {
            $candidate = $this->normalize($value);

            if ($candidate === $normalized) {
                return true;
            }

            if (strlen($normalized) >= 3 && str_starts_with($candidate, $normalized)) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $value): string
    {
        return Str::lower(Str::ascii(trim($value)));
    }
}
