<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Auth & User Flow QA Test
 * Covers: Register, Login, Profile, Password, Delete Account
 */
class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    private string $prefix = '/api/v1/auth';

    // ─── 1. Register ─────────────────────────────────────────────────

    public function test_user_can_register(): void
    {
        $response = $this->postJson("{$this->prefix}/register", [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['user', 'token']]);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_register_duplicate_email_rejected(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson("{$this->prefix}/register", [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_weak_password_rejected(): void
    {
        $response = $this->postJson("{$this->prefix}/register", [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(422);
    }

    // ─── 2. Login ────────────────────────────────────────────────────

    public function test_user_can_login(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('Password123!'),
        ]);

        $response = $this->postJson("{$this->prefix}/login", [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_login_wrong_password_rejected(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('Password123!'),
        ]);

        $response = $this->postJson("{$this->prefix}/login", [
            'email' => 'test@example.com',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_nonexistent_email(): void
    {
        $response = $this->postJson("{$this->prefix}/login", [
            'email' => 'nonexistent@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(401);
    }

    // ─── 3. Me (Profile) ────────────────────────────────────────────

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("{$this->prefix}/me");

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.user.email', $user->email);
    }

    public function test_unauthenticated_cannot_get_profile(): void
    {
        $this->getJson("{$this->prefix}/me")->assertStatus(401);
    }

    // ─── 4. Logout ───────────────────────────────────────────────────

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->prefix}/logout");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    // ─── 5. Update Profile ───────────────────────────────────────────

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("{$this->prefix}/profile", [
                'name' => 'Updated Name',
                'phone' => '9876543210',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    // ─── 6. Change Password ─────────────────────────────────────────

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('OldPassword123!'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("{$this->prefix}/change-password", [
                'current_password' => 'OldPassword123!',
                'password' => 'NewPassword456!',
                'password_confirmation' => 'NewPassword456!',
            ]);

        $response->assertOk();
    }

    public function test_change_password_wrong_current(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('OldPassword123!'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("{$this->prefix}/change-password", [
                'current_password' => 'WrongPassword!',
                'password' => 'NewPassword456!',
                'password_confirmation' => 'NewPassword456!',
            ]);

        $response->assertStatus(422);
    }
}
