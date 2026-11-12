<?php

namespace Modules\Listings\Actions;

use Illuminate\Support\Str;
use Modules\Listings\Support\MexicoLocationCatalog;

final class ResolveSearchLocation
{
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

        $states = MexicoLocationCatalog::all();

        if ($statePart !== null) {
            foreach ($states as $value => $state) {
                if (! $this->matches($statePart, [$value, $state['label']])) {
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
            if ($this->matches($query, [$value, $state['label']])) {
                return ['state' => $value];
            }
        }

        return null;
    }

    private function findCity(string $query, string $stateValue): ?string
    {
        $normalized = $this->normalize($query);
        $candidates = [];

        foreach (MexicoLocationCatalog::municipalities($stateValue) as $city) {
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
