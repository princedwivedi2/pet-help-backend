<?php

namespace Tests\Feature\Api\V1;

use App\Models\IncidentLog;
use App\Models\Pet;
use App\Models\SosRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private string $prefix = '/api/v1/admin';

    private function adminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    // ─── Stats ───────────────────────────────────────────────────────

    public function test_admin_can_view_stats(): void
    {
        $admin = $this->adminUser();
        User::factory()->count(3)->create();
        Pet::factory()->count(5)->create();
        SosRequest::factory()->count(2)->pending()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("{$this->prefix}/stats");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['stats' => ['total_users', 'total_pets', 'active_sos', 'total_sos', 'total_incidents']],
            ]);
    }

    public function test_regular_user_cannot_view_stats(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user, 'sanctum')
            ->getJson("{$this->prefix}/stats")
            ->assertStatus(403);
    }

    public function test_unauthenticated_cannot_view_stats(): void
    {
        $this->getJson("{$this->prefix}/stats")
            ->assertStatus(401);
    }

    // ─── Users ───────────────────────────────────────────────────────

    public function test_admin_can_list_users(): void
    {
        $admin = $this->adminUser();
        User::factory()->count(5)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("{$this->prefix}/users");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['users', 'pagination'],
            ]);
    }

    public function test_admin_can_filter_users_by_role(): void
    {
        $admin = $this->adminUser();
        User::factory()->count(3)->create(['role' => 'user']);
        User::factory()->count(2)->create(['role' => 'vet']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("{$this->prefix}/users?role=vet");

        $response->assertOk()
            ->assertJsonPath('data.pagination.total', 2);
    }

    public function test_admin_can_search_users(): void
    {
        $admin = $this->adminUser();
        User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        User::factory()->create(['name' => 'John Smith']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("{$this->prefix}/users?search=Jane");

        $response->assertOk()
            ->assertJsonPath('data.pagination.total', 1);
    }

    // ─── Update Role ─────────────────────────────────────────────────

    public function test_admin_can_update_user_role(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("{$this->prefix}/users/{$user->id}/role", [
                'role' => 'vet',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'vet']);
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("{$this->prefix}/users/{$admin->id}/role", [
                'role' => 'user',
            ]);

        $response->assertStatus(422);
    }

    public function test_regular_user_cannot_update_role(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $target = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson("{$this->prefix}/users/{$target->id}/role", ['role' => 'admin'])
            ->assertStatus(403);
    }

    // ─── SOS Dashboard ──────────────────────────────────────────────

    public function test_admin_can_list_all_sos(): void
    {
        $admin = $this->adminUser();
        SosRequest::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("{$this->prefix}/sos");

        $response->assertOk()
            ->assertJsonStructure(['data' => ['sos_requests', 'pagination']]);
    }

    public function test_admin_can_filter_sos_by_status(): void
    {
        $admin = $this->adminUser();
        SosRequest::factory()->count(2)->pending()->create();
        SosRequest::factory()->count(1)->completed()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("{$this->prefix}/sos?status=pending");

        $response->assertOk()
            ->assertJsonPath('data.pagination.total', 2);
    }

    // ─── Incidents Dashboard ─────────────────────────────────────────

    public function test_admin_can_list_all_incidents(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create();
        $pet = Pet::factory()->forUser($user)->create();
        IncidentLog::factory()->count(4)->create([
            'user_id' => $user->id,
            'pet_id' => $pet->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("{$this->prefix}/incidents");

        $response->assertOk()
            ->assertJsonStructure(['data' => ['incidents', 'pagination']]);
    }
}
