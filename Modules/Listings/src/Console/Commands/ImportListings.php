<?php

namespace Modules\Listings\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;
use Modules\Listings\Actions\ImportListingData;
use Modules\Listings\Support\ListingImportFileReader;
use Modules\Listings\Support\ListingImportNormalizer;
use RuntimeException;
use Throwable;

class ImportListings extends Command
{
    protected $signature = 'listings:import
        {file : Ruta del archivo JSON o CSV con las terrazas}
        {--images= : Carpeta donde están las imágenes referenciadas}
        {--publish : Publicar las terrazas que cumplan los requisitos}';

    protected $description = 'Importa terrazas reales a la cuenta Directorio para que sus dueños puedan reclamarlas';

    public function handle(
        ImportListingData $import,
        ListingImportFileReader $reader,
        ListingImportNormalizer $normalizer,
    ): int {
        $file = (string) $this->argument('file');

        if (! is_file($file)) {
            $this->components->error("No existe el archivo {$file}.");

            return self::FAILURE;
        }

        $directory = Team::query()->where('is_directory', true)->first();

        if ($directory === null) {
            $this->components->error('Primero crea la cuenta Directorio con `php artisan app:directory-team`.');

            return self::FAILURE;
        }

        $imagesRoot = $this->option('images');

        if (is_string($imagesRoot) && ! is_dir($imagesRoot)) {
            $this->components->error("La carpeta de imágenes {$imagesRoot} no existe.");

            return self::FAILURE;
        }

        try {
            $result = $reader->read($file, pathinfo($file, PATHINFO_EXTENSION));
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($result['rows'] === []) {
            $this->components->warn('El archivo no contiene terrazas.');

            return self::SUCCESS;
        }

        $publish = (bool) $this->option('publish');
        $created = 0;
        $updated = 0;
        $published = 0;
        $failed = 0;

        foreach ($normalizer->normalize($result['rows'], $result['first_line']) as $row) {
            if ($row['errors'] !== []) {
                $failed++;
                $this->components->error('Fila '.$row['line'].': '.implode(' ', $row['errors']));

                continue;
            }

            try {
                $outcome = $import->handle(
                    $directory,
                    $row['data'],
                    is_string($imagesRoot) ? $imagesRoot : null,
                    $publish,
                );

                $outcome['created'] ? $created++ : $updated++;
                $published += $outcome['published'] ? 1 : 0;

                $this->components->twoColumnDetail(
                    $outcome['created'] ? 'Creada' : 'Actualizada',
                    sprintf('%s (%s)', $outcome['listing']->name, $outcome['listing']->slug),
                );

                foreach ([...$row['warnings'], ...$outcome['warnings']] as $warning) {
                    $this->components->warn($warning);
                }
            } catch (Throwable $exception) {
                $failed++;
                $this->components->error('Fila '.$row['line'].': '.$exception->getMessage());
            }
        }

        $this->newLine();
        $this->components->info("Creadas: {$created} · Actualizadas: {$updated} · Publicadas: {$published} · Errores: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
