<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Register a test-only route gated by role middleware
        Route::middleware(['api', 'auth:sanctum', 'role:admin'])
            ->prefix('api/v1')
            ->get('/test-admin', fn () => response()->json(['ok' => true]));
    }

    public function test_admin_can_access_admin_route(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/test-admin');

        $response->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_regular_user_blocked_from_admin_route(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/test-admin');

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_vet_blocked_from_admin_route(): void
    {
        $vet = User::factory()->create(['role' => 'vet']);

        $response = $this->actingAs($vet, 'sanctum')
            ->getJson('/api/v1/test-admin');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_blocked_from_admin_route(): void
    {
        $this->getJson('/api/v1/test-admin')
            ->assertStatus(401);
    }

    public function test_default_user_role(): void
    {
        $user = User::factory()->create();

        // Default role from migration is 'user'
        $this->assertEquals('user', $user->role);
        $this->assertTrue($user->isUser());
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isVet());
    }
}
