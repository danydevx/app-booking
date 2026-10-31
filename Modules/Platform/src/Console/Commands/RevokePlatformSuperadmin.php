<?php

namespace Modules\Platform\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Platform\Support\PlatformAccess;

class RevokePlatformSuperadmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:revoke-platform-superadmin
        {email : Existing user email}
        {--force : Skip the interactive confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revoke protected platform superadmin access from an existing user';

    public function handle(): int
    {
        $email = strtolower((string) $this->argument('email'));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            $this->error('No user exists with that email address.');

            return self::FAILURE;
        }

        if (! $user->hasRole(PlatformAccess::ROLE)) {
            $this->info('The user does not have platform superadmin access.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Revoke platform superadmin access from {$user->email}?")) {
            $this->warn('Platform access was not revoked.');

            return self::FAILURE;
        }

        $user->removeRole(PlatformAccess::ROLE);

        activity('moderation')
            ->performedOn($user)
            ->withProperties([
                'email' => $user->email,
                'action' => 'revoke',
                'actor' => 'console',
            ])
            ->log('platform.superadmin.revoked');

        Log::notice('Platform superadmin access revoked.', [
            'event' => 'platform.superadmin.revoked',
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        $this->info("Platform superadmin access revoked from {$user->email}.");

        return self::SUCCESS;
    }
}
