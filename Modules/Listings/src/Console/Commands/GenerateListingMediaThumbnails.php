<?php

namespace Modules\Listings\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Modules\Listings\Actions\GenerateListingMediaThumbnail;
use Modules\Listings\Enums\ListingMediaType;
use Modules\Listings\Models\ListingMedia;
use Throwable;

class GenerateListingMediaThumbnails extends Command
{
    protected $signature = 'listings:media:thumbnails
        {--dry-run : Report the work without writing any thumbnail}
        {--batch=100 : Number of media records processed per batch}';

    protected $description = 'Generate the missing 400x400 WebP thumbnails for listing images';

    public function __construct(
        private readonly GenerateListingMediaThumbnail $thumbnails,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $batch = max(1, (int) $this->option('batch'));

        $generated = 0;
        $skipped = 0;
        $missing = 0;
        $failed = 0;

        if ($dryRun) {
            $this->info('Dry run: no se escribirán archivos.');
        }

        ListingMedia::query()
            ->where('type', ListingMediaType::Image->value)
            ->chunkById($batch, function (Collection $media) use ($dryRun, &$generated, &$skipped, &$missing, &$failed): void {
                foreach ($media as $item) {
                    $thumbnailPath = $item->thumbnailPath();

                    if ($thumbnailPath === null) {
                        continue;
                    }

                    $disk = Storage::disk($item->disk);

                    if (! $disk->exists($item->path)) {
                        $missing++;
                        $this->warn("Original faltante #{$item->id}: {$item->disk}:{$item->path}");

                        continue;
                    }

                    if ($disk->exists($thumbnailPath)) {
                        $skipped++;

                        continue;
                    }

                    if ($dryRun) {
                        $generated++;
                        $this->line("[dry-run] Se generaría #{$item->id}: {$item->disk}:{$thumbnailPath}");

                        continue;
                    }

                    try {
                        $this->thumbnails->handle($item);
                        $generated++;
                        $this->line("Generada #{$item->id}: {$item->disk}:{$thumbnailPath}");
                    } catch (Throwable $exception) {
                        $failed++;
                        report($exception);
                        $this->error("Error #{$item->id}: {$exception->getMessage()}");
                    }
                }
            });

        $this->newLine();
        $this->info(sprintf(
            'Miniaturas: %d generadas, %d omitidas, %d originales faltantes, %d errores.',
            $generated,
            $skipped,
            $missing,
            $failed,
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
