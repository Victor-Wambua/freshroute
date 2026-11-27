<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'fr:make-admin {email} {name}';

    protected $description = 'Create a platform admin (TOTP enrolment is forced at first sign-in)';

    public function handle(): int
    {
        $password = $this->secret('Password (min 12 chars)');
        if (strlen((string) $password) < 12) {
            $this->error('Password too short.');

            return self::FAILURE;
        }
        $u = new User(['name' => $this->argument('name'), 'email' => strtolower($this->argument('email')), 'password' => $password, 'consent_at' => now(), 'notification_prefs' => ['database' => true, 'mail' => true]]);
        $u->role = Role::PlatformAdmin;
        $u->save();
        $this->info('Platform admin created. They must enrol TOTP at first sign-in.');

        return self::SUCCESS;
    }
}
