<?php

namespace Modules\Listings\Support;

use Modules\Listings\Contracts\Geocoder;

final class NullGeocoder implements Geocoder
{
    public function geocode(string $address): ?array
    {
        return null;
    }
}
