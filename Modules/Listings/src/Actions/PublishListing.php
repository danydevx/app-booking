<?php

namespace Modules\Listings\Actions;

use Illuminate\Validation\ValidationException;
use Modules\Listings\Enums\ListingStatus;
use Modules\Listings\Models\Listing;
use Modules\Platform\Support\GeographicCoverageService;

final class PublishListing
{
    /**
     * @throws ValidationException
     */
    public function handle(Listing $listing): Listing
    {
        $errors = [];

        if (! $listing->images()->exists()) {
            $errors['listing'][] = 'Agrega al menos una imagen antes de publicar.';
        }

        if (blank($listing->address)) {
            $errors['listing'][] = 'La dirección es obligatoria para publicar.';
        }

        if ($listing->latitude === null || $listing->longitude === null) {
            $errors['listing'][] = 'La ubicación en el mapa es obligatoria para publicar.';
        }

        if (! app(GeographicCoverageService::class)->isEligible($listing->state, $listing->city)) {
            $errors['listing'][] = 'La ubicación del espacio no está dentro de la cobertura habilitada.';
        }

        if (blank($listing->contact_email) && blank($listing->contact_phone) && blank($listing->whatsapp_phone)) {
            $errors['listing'][] = 'Agrega al menos un medio de contacto para publicar.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $listing->forceFill([
            'status' => ListingStatus::Published,
            'published_at' => $listing->published_at ?? now(),
        ])->save();

        return $listing->refresh();
    }
}
