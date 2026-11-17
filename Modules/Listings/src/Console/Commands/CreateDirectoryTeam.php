<?php

namespace Modules\Listings\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;

class CreateDirectoryTeam extends Command
{
    protected $signature = 'app:directory-team {--name= : Nombre de la cuenta Directorio}';

    protected $description = 'Crea o muestra la cuenta Directorio que contiene las terrazas sin reclamar';

    public function handle(): int
    {
        $team = Team::query()->where('is_directory', true)->first();

        if ($team !== null) {
            $this->components->info("La cuenta Directorio ya existe: {$team->name} ({$team->slug}, id {$team->id}).");

            return self::SUCCESS;
        }

        $name = trim((string) ($this->option('name') ?: config('listings.directory.team_name', 'Directorio')));

        $team = new Team([
            'name' => $name,
            'is_personal' => false,
            'timezone' => (string) config('listings.default_timezone', 'America/Mexico_City'),
        ]);
        $team->forceFill(['is_directory' => true])->save();

        $this->components->info("Cuenta Directorio creada: {$team->name} ({$team->slug}, id {$team->id}).");

        return self::SUCCESS;
    }
}
