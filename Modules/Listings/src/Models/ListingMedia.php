<?php

namespace Modules\Listings\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Modules\Listings\Database\Factories\ListingMediaFactory;
use Modules\Listings\Enums\ListingMediaType;

/**
 * @property int $id
 * @property int $listing_id
 * @property ListingMediaType $type
 * @property string $disk
 * @property string $path
 * @property string $mime_type
 * @property int $size_bytes
 * @property int|null $width
 * @property int|null $height
 * @property int|null $duration_seconds
 * @property string|null $alt_text
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'type',
    'disk',
    'path',
    'mime_type',
    'size_bytes',
    'width',
    'height',
    'duration_seconds',
    'alt_text',
    'sort_order',
])]
class ListingMedia extends Model
{
    /** @use HasFactory<ListingMediaFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function isImage(): bool
    {
        return $this->type === ListingMediaType::Image;
    }

    public function isVideo(): bool
    {
        return $this->type === ListingMediaType::Video;
    }

    /**
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => Storage::disk($this->disk)->url($this->path));
    }

    /**
     * Deterministic path for the square WebP thumbnail derived from the
     * original file. No database column is needed.
     */
    public function thumbnailPath(): ?string
    {
        if (! $this->isImage()) {
            return null;
        }

        $directory = pathinfo($this->path, PATHINFO_DIRNAME);
        $filename = pathinfo($this->path, PATHINFO_FILENAME);

        return ($directory === '.' ? '' : $directory.'/')
            .'thumbs/'.$filename.'.webp';
    }

    public function hasThumbnail(): bool
    {
        $thumbnailPath = $this->thumbnailPath();

        return $thumbnailPath !== null
            && Storage::disk($this->disk)->exists($thumbnailPath);
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            $thumbnailPath = $this->thumbnailPath();

            if ($thumbnailPath === null || ! Storage::disk($this->disk)->exists($thumbnailPath)) {
                return null;
            }

            return Storage::disk($this->disk)->url($thumbnailPath);
        });
    }

    protected static function newFactory(): ListingMediaFactory
    {
        return ListingMediaFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ListingMediaType::class,
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'duration_seconds' => 'integer',
            'sort_order' => 'integer',
        ];
    }
}
