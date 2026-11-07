<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $app_name
 * @property string|null $logo_path
 * @property string|null $support_email
 * @property string|null $from_address
 * @property string|null $legal_terms_url
 * @property string|null $legal_privacy_url
 * @property int|null $updated_by_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'app_name',
    'logo_path',
    'support_email',
    'from_address',
    'legal_terms_url',
    'legal_privacy_url',
    'updated_by_id',
])]
class PlatformSetting extends Model
{
    /**
     * Get the single platform settings row, creating defaults when missing.
     */
    public static function current(): self
    {
        $settings = static::query()->orderBy('id')->first();

        if ($settings !== null) {
            return $settings;
        }

        static::query()->insertOrIgnore([
            'id' => 1,
            'app_name' => (string) config('app.name', 'Terrazas'),
            'support_email' => config('mail.from.address'),
            'from_address' => config('mail.from.address'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return static::query()->findOrFail(1);
    }

    public function logoUrl(): ?string
    {
        if (blank($this->logo_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }
}
