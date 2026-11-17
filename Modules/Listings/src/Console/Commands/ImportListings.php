<?php

namespace Modules\Listings\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;
use Modules\Listings\Actions\ImportListingData;
use Throwable;

class ImportListings extends Command
{
    protected $signature = 'listings:import
        {file : Ruta del archivo JSON o CSV con las terrazas}
        {--images= : Carpeta donde están las imágenes referenciadas}
        {--publish : Publicar las terrazas que cumplan los requisitos}';

    protected $description = 'Importa terrazas reales a la cuenta Directorio para que sus dueños puedan reclamarlas';

    public function handle(ImportListingData $import): int
    {
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
            $rows = $this->readRows($file);
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($rows === []) {
            $this->components->warn('El archivo no contiene terrazas.');

            return self::SUCCESS;
        }

        $publish = (bool) $this->option('publish');
        $created = 0;
        $updated = 0;
        $published = 0;
        $failed = 0;

        foreach ($rows as $index => $row) {
            try {
                $result = $import->handle(
                    $directory,
                    $row,
                    is_string($imagesRoot) ? $imagesRoot : null,
                    $publish,
                );

                $result['created'] ? $created++ : $updated++;
                $published += $result['published'] ? 1 : 0;

                $this->components->twoColumnDetail(
                    $result['created'] ? 'Creada' : 'Actualizada',
                    sprintf('%s (%s)', $result['listing']->name, $result['listing']->slug),
                );

                foreach ($result['warnings'] as $warning) {
                    $this->components->warn($warning);
                }
            } catch (Throwable $exception) {
                $failed++;
                $this->components->error('Fila '.($index + 1).': '.$exception->getMessage());
            }
        }

        $this->newLine();
        $this->components->info("Creadas: {$created} · Actualizadas: {$updated} · Publicadas: {$published} · Errores: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readRows(string $file): array
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if ($extension === 'json') {
            return $this->readJson($file);
        }

        if ($extension === 'csv') {
            return $this->readCsv($file);
        }

        throw new \RuntimeException('El archivo debe ser .json o .csv.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readJson(string $file): array
    {
        $contents = file_get_contents($file);
        $decoded = json_decode((string) $contents, true);

        if (! is_array($decoded)) {
            throw new \RuntimeException('El JSON no es válido.');
        }

        if (isset($decoded['listings']) && is_array($decoded['listings'])) {
            $decoded = $decoded['listings'];
        }

        if (! array_is_list($decoded)) {
            $decoded = [$decoded];
        }

        return array_values(array_filter($decoded, 'is_array'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readCsv(string $file): array
    {
        $handle = fopen($file, 'r');

        if ($handle === false) {
            throw new \RuntimeException('No se pudo abrir el CSV.');
        }

        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);

            return [];
        }

        $headers = array_map(
            fn (mixed $header): string => trim((string) $header),
            $headers,
        );

        $rows = [];

        while (($values = fgetcsv($handle)) !== false) {
            if ($values === [null]) {
                continue;
            }

            $row = [];

            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $row[$header] = $values[$index] ?? null;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }
}
