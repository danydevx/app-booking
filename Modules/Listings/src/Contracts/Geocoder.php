<?php

namespace Modules\Listings\Contracts;

interface Geocoder
{
    /**
     * Resolve an address to map coordinates.
     *
     * @return array{lat: float, lng: float}|null
     */
    public function geocode(string $address): ?array;
}
