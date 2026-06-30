<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * SAFETY: Demo accounts (admin@petsathi.com / vet@petsathi.com / test@example.com)
     * have hardcoded weak passwords and MUST NEVER run in production. Reference data
     * (emergency guides, vet directory) still seeds in any environment.
     *
     * For production admin bootstrap, use `php artisan admin:create` instead.
     */
    public function run(): void
    {
        if (app()->environment(['local', 'testing'])) {
            $this->seedDemoAccounts();
        } else {
            $this->command?->warn(
                'Skipping demo account seeding in environment: ' . app()->environment()
                . '. Use `php artisan admin:create` to bootstrap production admin.'
            );
        }

        // Reference data — safe in any environment.
        $this->call([
            EmergencyCategorySeeder::class,
            EmergencyGuideSeeder::class,
            VetProfileSeeder::class,
        ]);

        if (app()->environment(['local', 'testing'])) {
            $this->linkDemoVetToProfile();
        }
    }

    /**
     * Demo accounts with predictable credentials. Local/testing only.
     */
    private function seedDemoAccounts(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@petsathi.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('admin123'),
                'email_verified_at' => now(),
            ]
        );
        if ($admin->wasRecentlyCreated) {
            $admin->role = 'admin';
            $admin->save();
        }

        $vetUser = User::firstOrCreate(
            ['email' => 'vet@petsathi.com'],
            [
                'name' => 'Dr. Sarah Johnson',
                'password' => bcrypt('vet123'),
                'email_verified_at' => now(),
            ]
        );
        if ($vetUser->wasRecentlyCreated) {
            $vetUser->role = 'vet';
            $vetUser->save();
        }

        $testUser = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        if ($testUser->wasRecentlyCreated) {
            $testUser->role = 'user';
            $testUser->save();
        }
    }

    private function linkDemoVetToProfile(): void
    {
        $vetUser = User::where('email', 'vet@petsathi.com')->first();
        if (!$vetUser) {
            return;
        }
        $vetProfile = \App\Models\VetProfile::where('email', 'info@downtownpetemergency.com')->first();
        if ($vetProfile) {
            // vet_status is not in $fillable; forceFill bypasses the guard for the seeder.
            $vetProfile->forceFill([
                'user_id' => $vetUser->id,
                'vet_status' => 'approved',
            ])->save();
        }
    }
}
