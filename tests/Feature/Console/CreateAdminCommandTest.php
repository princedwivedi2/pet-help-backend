<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the admin:create Artisan command.
 */
class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    // ─── Success paths ───────────────────────────────────────────────

    public function test_creates_admin_when_no_user_exists(): void
    {
        $this->artisan('admin:create', [
            '--email'    => 'newadmin@example.com',
            '--name'     => 'Super Admin',
            '--password' => 'securepass',
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Admin user created');

        $this->assertDatabaseHas('users', [
            'email' => 'newadmin@example.com',
            'name'  => 'Super Admin',
            'role'  => 'admin',
        ]);
    }

    public function test_skips_when_admin_already_exists(): void
    {
        User::factory()->create([
            'email'    => 'existing@example.com',
            'role'     => 'admin',
        ]);

        $this->artisan('admin:create', [
            '--email'    => 'existing@example.com',
            '--password' => 'securepass',
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('already exists');

        $this->assertDatabaseCount('users', 1);
    }

    // ─── Failure paths ───────────────────────────────────────────────

    public function test_fails_when_email_is_invalid(): void
    {
        $this->artisan('admin:create', [
            '--email'    => 'not-an-email',
            '--password' => 'securepass',
        ])
            ->assertExitCode(1);
    }

    public function test_fails_when_password_is_too_short(): void
    {
        $this->artisan('admin:create', [
            '--email'    => 'short@example.com',
            '--password' => 'short',
        ])
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'short@example.com']);
    }

    public function test_warns_when_existing_user_has_non_admin_role(): void
    {
        User::factory()->create([
            'email' => 'regular@example.com',
            'role'  => 'user',
        ]);

        $this->artisan('admin:create', [
            '--email'    => 'regular@example.com',
            '--password' => 'securepass',
        ])
            ->assertExitCode(1);

        // Role should NOT have been changed
        $this->assertDatabaseHas('users', [
            'email' => 'regular@example.com',
            'role'  => 'user',
        ]);
    }
}
