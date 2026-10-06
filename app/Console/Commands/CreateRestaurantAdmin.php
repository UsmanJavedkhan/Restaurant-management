<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateRestaurantAdmin extends Command
{
    protected $signature = 'restaurant:admin {email} {--name=Restaurant Admin} {--generate : Generate a random password for a new local admin}';

    protected $description = 'Create a restaurant administrator without publishing default credentials';

    public function handle(): int
    {
        $email = Str::lower($this->argument('email'));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || User::where('email', $email)->exists()) {
            $this->error('Enter a valid email that is not already registered.');

            return self::FAILURE;
        }
        if ($this->option('generate') && ! app()->isLocal()) {
            $this->error('Generated console credentials are only supported locally.');

            return self::FAILURE;
        }
        $password = $this->option('generate') ? Str::password(20, symbols: false) : $this->secret('Administrator password (at least 12 characters)');
        if (! is_string($password) || strlen($password) < 12) {
            $this->error('Use a password with at least 12 characters.');

            return self::FAILURE;
        }
        $user = User::create(['email' => $email, 'name' => $this->option('name'), 'password' => $password]);
        $user->forceFill(['role' => 'admin', 'is_active' => true])->save();
        $this->info('Administrator created: '.$email);
        if ($this->option('generate')) {
            $this->line('Generated password: '.$password);
        }

        return self::SUCCESS;
    }
}
