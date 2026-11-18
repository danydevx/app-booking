<?php

namespace Modules\Listings\Support;

use Illuminate\Support\Str;
use Modules\Listings\Models\ListingEventType;

/**
 * Maps raw import rows to the canonical listing payload and reports problems
 * with the values that reference the event types and characteristics catalogs.
 */
final class ListingImportNormalizer
{
    /**
     * Column aliases written by people in Spanish (or English). Keys are
     * normalized column names; values are canonical listing fields.
     *
     * @var array<string, string>
     */
    private const COLUMN_ALIASES = [
        'titulo' => 'name',
        'title' => 'name',
        'nombre' => 'name',
        'capacidad' => 'capacity',
        'aforo' => 'capacity',
        'descripcion' => 'description',
        'tipo_evento' => 'event_types',
        'tipos_evento' => 'event_types',
        'tipos_de_evento' => 'event_types',
        'eventos' => 'event_types',
        'caracteristicas' => 'services',
        'servicios' => 'services',
        'amenidades' => 'services',
        'referencia' => 'external_ref',
        'ref' => 'external_ref',
        'direccion' => 'address',
        'municipio' => 'city',
        'ciudad' => 'city',
        'estado' => 'state',
        'pais' => 'country',
        'codigo_postal' => 'postal_code',
        'cp' => 'postal_code',
        'reglas' => 'rules',
        'contacto_nombre' => 'contact_name',
        'contacto_email' => 'contact_email',
        'contacto_correo' => 'contact_email',
        'contacto_telefono' => 'contact_phone',
        'imagenes' => 'images',
        'facebook' => 'facebook_url',
        'fb' => 'facebook_url',
        'instagram' => 'instagram_url',
        'instagram_url' => 'instagram_url',
        'tiktok' => 'tiktok_url',
        'google_maps' => 'google_maps_url',
        'maps' => 'google_maps_url',
        'maps_url' => 'google_maps_url',
    ];

    /**
     * Canonical fields accepted by the import action.
     *
     * @var array<int, string>
     */
    private const FIELDS = [
        'name',
        'external_ref',
        'description',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'latitude',
        'longitude',
        'capacity',
        'price_from_cents',
        'price_weekday_cents',
        'price_weekend_cents',
        'included_hours',
        'included_people',
        'extra_hour_cents',
        'extra_person_cents',
        'deposit_required_cents',
        'price_notes',
        'currency',
        'timezone',
        'services',
        'details',
        'daily_pricing',
        'rules',
        'contact_name',
        'contact_email',
        'contact_phone',
        'whatsapp_phone',
        'facebook_url',
        'instagram_url',
        'tiktok_url',
        'google_maps_url',
        'event_types',
        'images',
    ];

    /**
     * @var array<string, string>
     */
    private const EVENT_TYPE_ALIASES = [
        'boda' => 'bodas',
        'bodas' => 'bodas',
        'corporativo' => 'eventos-corporativos',
        'corporativos' => 'eventos-corporativos',
        'evento-corporativo' => 'eventos-corporativos',
        'xv' => 'xv-anos',
        'xv-anos' => 'xv-anos',
        'quince' => 'xv-anos',
        'quinceanera' => 'xv-anos',
        'quinceaneras' => 'xv-anos',
        'bautizo' => 'bautizos',
        'bautizos' => 'bautizos',
        'cumple' => 'cumpleanos',
        'cumpleanos' => 'cumpleanos',
        'graduacion' => 'graduaciones',
        'graduaciones' => 'graduaciones',
        'comunion' => 'primera-comunion',
        'primera-comunion' => 'primera-comunion',
        'baby-shower' => 'baby-shower',
        'despedida' => 'despedidas-de-soltera',
        'despedidas-de-soltera' => 'despedidas-de-soltera',
        'aniversario' => 'aniversarios',
        'aniversarios' => 'aniversarios',
        'reunion-familiar' => 'reuniones-familiares',
        'reuniones-familiares' => 'reuniones-familiares',
        'fiesta-infantil' => 'fiestas-infantiles',
        'fiestas-infantiles' => 'fiestas-infantiles',
        'otro' => 'otros-eventos',
        'otros' => 'otros-eventos',
        'otros-eventos' => 'otros-eventos',
    ];

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{
     *     line: int,
     *     data: array<string, mixed>,
     *     errors: array<int, string>,
     *     warnings: array<int, string>
     * }>
     */
    public function normalize(array $rows, int $firstLine = 1): array
    {
        $eventTypes = $this->eventTypeMap();
        $services = $this->serviceMap();

        $normalized = [];

        foreach (array_values($rows) as $index => $row) {
            $errors = [];
            $warnings = [];
            $data = [];

            foreach ($row as $column => $value) {
                $key = $this->column((string) $column);

                if ($key === null) {
                    continue;
                }

                switch ($key) {
                    case 'name':
                        $data['name'] = $this->string($value);
                        break;
                    case 'description':
                        $data['description'] = $this->string($value);
                        break;
                    case 'external_ref':
                        $data['external_ref'] = $this->string($value);
                        break;
                    case 'capacity':
                        [$data['capacity'], $error] = $this->integer($value);
                        if ($error !== null) {
                            $errors[] = $error;
                        }
                        break;
                    case 'latitude':
                    case 'longitude':
                        [$data[$key], $error] = $this->decimal($value, $key);
                        if ($error !== null) {
                            $errors[] = $error;
                        }
                        break;
                    case 'event_types':
                        [$values, $unknown] = $this->resolveList($value, $eventTypes);

                        if ($values !== []) {
                            $data['event_types'] = $values;
                        }

                        foreach ($unknown as $item) {
                            $warnings[] = "Tipo de evento desconocido: {$item}.";
                        }
                        break;
                    case 'services':
                        [$values, $unknown] = $this->resolveList($value, $services);

                        if ($values !== []) {
                            $data['services'] = $values;
                        }

                        foreach ($unknown as $item) {
                            $warnings[] = "Característica desconocida: {$item}.";
                        }
                        break;
                    case 'images':
                        $data['images'] = $this->stringList($value);
                        break;
                    case 'facebook_url':
                    case 'instagram_url':
                    case 'tiktok_url':
                    case 'google_maps_url':
                        $network = array_search($key, SocialProfileLink::COLUMNS, true);
                        $url = SocialProfileLink::normalize($this->string($value));

                        if ($url === null || ! is_string($network)) {
                            break;
                        }

                        if (! SocialProfileLink::isValid($network, $url)) {
                            $label = SocialProfileLink::NETWORKS[$network];
                            $warnings[] = "El enlace de {$label} no es válido; se omitió.";

                            break;
                        }

                        $data[$key] = $url;
                        break;
                    case 'details':
                    case 'daily_pricing':
                        [$value, $error] = $this->structured($value);

                        if ($error !== null) {
                            $warnings[] = $error;
                        } elseif (is_array($value)) {
                            $data[$key] = $value;
                        }
                        break;
                    default:
                        $value = $this->string($value);

                        if ($value !== null) {
                            $data[$key] = $value;
                        }
                }
            }

            $this->validate($data, $errors);

            $normalized[] = [
                'line' => $firstLine + $index,
                'data' => $data,
                'errors' => $errors,
                'warnings' => array_values(array_unique($warnings)),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $errors
     */
    private function validate(array $data, array &$errors): void
    {
        $name = $data['name'] ?? null;

        if (! is_string($name) || trim($name) === '') {
            $errors[] = 'Cada terraza necesita un título.';
        } elseif (mb_strlen($name) > 120) {
            $errors[] = 'El título no puede superar los 120 caracteres.';
        }

        $limits = [
            'description' => [5000, 'La descripción'],
            'address' => [255, 'La dirección'],
            'city' => [120, 'El municipio'],
            'state' => [120, 'El estado'],
            'country' => [120, 'El país'],
            'postal_code' => [20, 'El código postal'],
            'rules' => [2000, 'Las reglas'],
            'contact_name' => [120, 'El nombre de contacto'],
            'contact_phone' => [40, 'El teléfono de contacto'],
            'whatsapp_phone' => [40, 'El WhatsApp'],
            'external_ref' => [120, 'La referencia externa'],
        ];

        foreach ($limits as $field => [$limit, $label]) {
            $value = $data[$field] ?? null;

            if (is_string($value) && mb_strlen($value) > $limit) {
                $errors[] = "{$label} no puede superar los {$limit} caracteres.";
            }
        }

        $email = $data['contact_email'] ?? null;

        if (is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'El correo de contacto no es válido.';
        }
    }

    private function column(string $column): ?string
    {
        $key = Str::slug(trim($column), '_');

        if ($key === '') {
            return null;
        }

        $canonical = self::COLUMN_ALIASES[$key] ?? $key;

        return in_array($canonical, self::FIELDS, true) ? $canonical : null;
    }

    /**
     * @return array<int|string, string>
     */
    private function eventTypeMap(): array
    {
        $map = [];

        foreach (ListingEventType::query()->where('is_active', true)->get(['id', 'slug', 'name']) as $type) {
            $map[(string) $type->id] = $type->slug;
            $map[Str::slug($type->slug)] = $type->slug;
            $map[Str::slug($type->name)] = $type->slug;
        }

        foreach (self::EVENT_TYPE_ALIASES as $alias => $slug) {
            if (! isset($map[$slug])) {
                continue;
            }

            $map[Str::slug($alias)] = $slug;
        }

        return $map;
    }

    /**
     * @return array<string, string>
     */
    private function serviceMap(): array
    {
        $map = [];

        foreach (TerraceServiceCatalog::all() as $key => $label) {
            $map[$key] = $key;
            $map[Str::slug($label)] = $key;
        }

        foreach (TerraceServiceCatalog::legacyCommercialKeys() as $key) {
            $map[$key] = $key;
        }

        return $map;
    }

    /**
     * @param  array<int|string, string>  $map
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function resolveList(mixed $value, array $map): array
    {
        $resolved = [];
        $unknown = [];

        foreach ($this->stringList($value) as $item) {
            $lookup = Str::slug($item);

            if (isset($map[$lookup])) {
                $resolved[] = $map[$lookup];

                continue;
            }

            if (isset($map[$item])) {
                $resolved[] = $map[$item];

                continue;
            }

            $unknown[] = $item;
        }

        return [array_values(array_unique($resolved)), $unknown];
    }

    /**
     * @return array<int, string>
     */
    private function stringList(mixed $value): array
    {
        if (is_string($value)) {
            $value = trim($value);

            if ($value === '') {
                return [];
            }

            if (str_starts_with($value, '[')) {
                $decoded = json_decode($value, true);
                $value = is_array($decoded) ? $decoded : $value;
            }
        }

        if (is_string($value)) {
            $value = explode('|', $value);
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn (mixed $item): bool => is_scalar($item))
            ->map(fn (mixed $item): string => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array{0: int|null, 1: string|null}
     */
    private function integer(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [null, null];
        }

        if (is_string($value)) {
            $value = str_replace([',', ' '], '', $value);
        }

        if (! is_numeric($value)) {
            return [null, 'La capacidad debe ser un número entero.'];
        }

        $number = (int) $value;

        if ($number < 1 || $number > 100000) {
            return [null, 'La capacidad debe estar entre 1 y 100000 personas.'];
        }

        return [$number, null];
    }

    /**
     * @return array{0: float|null, 1: string|null}
     */
    private function decimal(mixed $value, string $field): array
    {
        if ($value === null || $value === '') {
            return [null, null];
        }

        if (! is_numeric($value)) {
            return [null, "El campo {$field} debe ser numérico."];
        }

        $number = (float) $value;
        $min = $field === 'latitude' ? -90 : -180;
        $max = $field === 'latitude' ? 90 : 180;

        if ($number < $min || $number > $max) {
            return [null, "El campo {$field} está fuera de rango."];
        }

        return [$number, null];
    }

    /**
     * @return array{0: mixed, 1: string|null}
     */
    private function structured(mixed $value): array
    {
        if (is_array($value)) {
            return [$value, null];
        }

        if ($value === null || $value === '') {
            return [null, null];
        }

        $decoded = is_string($value) ? json_decode($value, true) : null;

        if (! is_array($decoded)) {
            return [null, 'El valor debe ser un objeto JSON; se omitió.'];
        }

        return [$decoded, null];
    }

    private function string(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
