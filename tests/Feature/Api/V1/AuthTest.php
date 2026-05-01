<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private string $prefix = '/api/v1/auth';

    // ─── Registration ────────────────────────────────────────────────

    public function test_register_success(): void
    {
        Event::fake([Registered::class]);

        $response = $this->postJson("{$this->prefix}/register", [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['user' => ['id', 'name', 'email'], 'token'],
            ])
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);

        $user = User::where('email', 'jane@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('Password1', $user->password));

        Event::assertDispatched(Registered::class);
    }

    public function test_register_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson("{$this->prefix}/register", [
            'name' => 'Dup User',
            'email' => 'taken@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_validation_weak_password(): void
    {
        $response = $this->postJson("{$this->prefix}/register", [
            'name' => 'Weak',
            'email' => 'weak@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_validation_missing_fields(): void
    {
        $response = $this->postJson("{$this->prefix}/register", []);

        $response->assertStatus(422);
    }

    // ─── Login ───────────────────────────────────────────────────────

    public function test_login_success(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => bcrypt('Password1'),
        ]);

        $response = $this->postJson("{$this->prefix}/login", [
            'email' => 'login@example.com',
            'password' => 'Password1',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['user', 'token'],
            ])
            ->assertJson(['success' => true]);
    }

    public function test_login_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'bad@example.com',
            'password' => bcrypt('Password1'),
        ]);

        $response = $this->postJson("{$this->prefix}/login", [
            'email' => 'bad@example.com',
            'password' => 'WrongPass1',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_login_nonexistent_user(): void
    {
        $response = $this->postJson("{$this->prefix}/login", [
            'email' => 'ghost@example.com',
            'password' => 'Password1',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_missing_fields(): void
    {
        $response = $this->postJson("{$this->prefix}/login", []);

        $response->assertStatus(422);
    }

    // ─── Me ──────────────────────────────────────────────────────────

    public function test_me_authenticated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("{$this->prefix}/me");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['user' => ['id' => $user->id, 'email' => $user->email]],
            ]);
    }

    public function test_me_unauthenticated(): void
    {
        $response = $this->getJson("{$this->prefix}/me");

        $response->assertStatus(401);
    }

    // ─── Logout ──────────────────────────────────────────────────────

    public function test_logout_success(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile-app')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("{$this->prefix}/logout");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_unauthenticated(): void
    {
        $response = $this->postJson("{$this->prefix}/logout");

        $response->assertStatus(401);
    }

    public function test_signed_email_verification_marks_email_verified(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $response = $this->getJson($url);

        $response->assertOk();
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_profile_update_ignores_sensitive_fields(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'password' => Hash::make('OriginalPass1'),
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("{$this->prefix}/profile", [
                'name' => 'Updated Name',
                'role' => 'admin',
                'password' => 'NewPassword1',
                'email_verified_at' => null,
            ]);

        $response->assertOk();

        $fresh = $user->fresh();
        $this->assertSame('Updated Name', $fresh->name);
        $this->assertSame('user', $fresh->role);
        $this->assertTrue(Hash::check('OriginalPass1', $fresh->password));
        $this->assertNotNull($fresh->email_verified_at);
    }

    public function test_vet_pending_can_login_with_notice(): void
    {
        $vetUser = User::factory()->create([
            'role' => 'vet',
            'email' => 'pending-vet@example.com',
            'password' => Hash::make('Password1'),
        ]);

        VetProfile::factory()->create([
            'user_id' => $vetUser->id,
            'vet_status' => 'pending',
            'verification_status' => 'pending',
        ]);

        $response = $this->postJson("{$this->prefix}/login", [
            'email' => 'pending-vet@example.com',
            'password' => 'Password1',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.vet.vet_status', 'pending')
            ->assertJsonStructure(['data' => ['login_notice']]);
    }

    public function test_vet_rejected_login_is_blocked(): void
    {
        $vetUser = User::factory()->create([
            'role' => 'vet',
            'email' => 'rejected-vet@example.com',
            'password' => Hash::make('Password1'),
        ]);

        VetProfile::factory()->create([
            'user_id' => $vetUser->id,
            'vet_status' => 'rejected',
            'verification_status' => 'rejected',
        ]);

        $response = $this->postJson("{$this->prefix}/login", [
            'email' => 'rejected-vet@example.com',
            'password' => 'Password1',
        ]);

        $response->assertStatus(403);
    }

    public function test_vet_suspended_login_is_blocked(): void
    {
        $vetUser = User::factory()->create([
            'role' => 'vet',
            'email' => 'suspended-vet@example.com',
            'password' => Hash::make('Password1'),
        ]);

        VetProfile::factory()->create([
            'user_id' => $vetUser->id,
            'vet_status' => 'suspended',
            'verification_status' => 'suspended',
        ]);

        $response = $this->postJson("{$this->prefix}/login", [
            'email' => 'suspended-vet@example.com',
            'password' => 'Password1',
        ]);

        $response->assertStatus(403);
    }
}
