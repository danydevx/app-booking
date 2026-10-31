<?php

namespace Modules\Platform\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Platform\Support\PlatformAccess;

class MakePlatformSuperadmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:make-platform-superadmin
        {email : Existing verified user email}
        {--force : Skip the interactive confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant protected platform superadmin access to an existing verified user';

    public function handle(): int
    {
        $email = strtolower((string) $this->argument('email'));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            $this->error('No user exists with that email address.');

            return self::FAILURE;
        }

        if (! $user->hasVerifiedEmail()) {
            $this->error('The user must verify their email before receiving platform access.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Grant platform superadmin access to {$user->email}?")) {
            $this->warn('Platform access was not granted.');

            return self::FAILURE;
        }

        $user->assignRole(PlatformAccess::ensure());

        activity('moderation')
            ->performedOn($user)
            ->withProperties([
                'email' => $user->email,
                'action' => 'grant',
                'actor' => 'console',
            ])
            ->log('platform.superadmin.granted');

        Log::notice('Platform superadmin access granted.', [
            'event' => 'platform.superadmin.granted',
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        $this->info("Platform superadmin access granted to {$user->email}.");

        return self::SUCCESS;
    }
}
