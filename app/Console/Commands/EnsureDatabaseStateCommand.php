<?php

namespace App\Console\Commands;

use Database\States\EnsureAdminSeeded;
use Database\States\EnsurePermissionsSeeded;
use Database\States\EnsureRolesSeeded;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:ensure-database-state-command')]
#[Description('Seed permissions, roles, and admin user if missing (idempotent).')]
class EnsureDatabaseStateCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        collect([
            new EnsurePermissionsSeeded,
            new EnsureRolesSeeded,
            new EnsureAdminSeeded,
        ])->each->__invoke();

        $this->components->info('Database state ensured.');

        return self::SUCCESS;
    }
}
