<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create
        {--email=admin@petsathi.com : Admin email address}
        {--name=Admin : Admin display name}
        {--password= : Admin password (prompted securely if omitted)}';

    protected $description = 'Create the initial admin user (idempotent — skips if an admin with that email already exists)';

    public function handle(): int
    {
        $email = $this->option('email');
        $name  = $this->option('name');

        // Validate email
        $validator = Validator::make(['email' => $email], ['email' => 'required|email']);
        if ($validator->fails()) {
            $this->error('Invalid email address: ' . $email);
            return self::FAILURE;
        }

        // Prompt for password securely when not supplied via option
        $password = $this->option('password')
            ?: $this->secret('Password for ' . $email);

        if (empty($password)) {
            $this->error('Password cannot be empty.');
            return self::FAILURE;
        }

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');
            return self::FAILURE;
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'              => $name,
                'password'          => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        if ($user->wasRecentlyCreated) {
            $user->role = 'admin';
            $user->save();
            $this->info("✓ Admin user created: {$email}");
            return self::SUCCESS;
        }

        if ($user->role !== 'admin') {
            $this->warn("User {$email} already exists but has role '{$user->role}'. No changes made.");
            $this->warn('To promote an existing user, update their role directly in the database or via the admin API.');
            return self::FAILURE;
        }

        $this->info("Admin user already exists: {$email} — no action taken.");
        return self::SUCCESS;
    }
}
