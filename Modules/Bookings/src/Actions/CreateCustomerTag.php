<?php

namespace Modules\Bookings\Actions;

use App\Models\Team;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Bookings\Models\CustomerTag;

final class CreateCustomerTag
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Team $team, array $data): CustomerTag
    {
        if (CustomerTag::query()->where('team_id', $team->id)->count() >= 20) {
            throw ValidationException::withMessages([
                'name' => 'La cuenta ya tiene el máximo de 20 etiquetas.',
            ]);
        }

        $name = trim((string) $data['name']);
        $baseSlug = Str::slug($name);

        if ($baseSlug === '') {
            throw ValidationException::withMessages([
                'name' => 'La etiqueta debe tener un nombre válido.',
            ]);
        }

        $slug = $baseSlug;
        $suffix = 2;

        while (CustomerTag::query()->where('team_id', $team->id)->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        $tag = new CustomerTag([
            'name' => $name,
            'slug' => $slug,
            'color' => (string) ($data['color'] ?? '#64748b'),
        ]);
        $tag->team_id = $team->id;
        $tag->save();

        return $tag;
    }
}
