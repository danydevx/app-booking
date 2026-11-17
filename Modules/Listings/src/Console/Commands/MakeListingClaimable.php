<?php

namespace Modules\Listings\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Modules\Listings\Actions\MarkListingAsClaimable;
use Modules\Listings\Models\Listing;
use Throwable;

class MakeListingClaimable extends Command
{
    protected $signature = 'listings:make-claimable
        {listing : Slug o id de la terraza}
        {--reason=Movida al Directorio desde el panel de plataforma.}';

    protected $description = 'Mueve una terraza existente al Directorio para que su dueño pueda reclamarla';

    public function handle(MarkListingAsClaimable $action): int
    {
        $key = (string) $this->argument('listing');

        $listing = Listing::query()
            ->when(ctype_digit($key), fn ($query) => $query->whereKey((int) $key))
            ->when(! ctype_digit($key), fn ($query) => $query->where('slug', $key))
            ->first();

        if ($listing === null) {
            $this->components->error("No encontramos la terraza {$key}.");

            return self::FAILURE;
        }

        $user = User::query()->orderBy('id')->first();

        if ($user === null) {
            $this->components->error('Necesitas al menos un usuario para registrar la operación.');

            return self::FAILURE;
        }

        try {
            $action->handle($listing, $user, (string) $this->option('reason'));
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Terraza {$listing->name} movida al Directorio. URL de reclamo: ".route('public.claims.create', ['listing' => $listing->slug]));

        return self::SUCCESS;
    }
}
