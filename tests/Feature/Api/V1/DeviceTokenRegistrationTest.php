<?php

namespace Tests\Feature\Api\V1;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * POST /api/v1/auth/device-token registration upsert behavior.
 *
 * FCM tokens are device-scoped. The same physical handset can present its token
 * to different user accounts over time (e.g. logout + login as someone else),
 * so registration must:
 *   - upsert by token (no duplicates)
 *   - re-assign user_id to the current caller
 *   - reactivate previously-deactivated rows
 *   - keep `users.fcm_token` in sync for legacy callers
 */
class DeviceTokenRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_user_can_register_a_new_device_token()
    {
        $user = User::factory()->create();

        $resp = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/device-token', [
                'token' => 'fresh-fcm-token-abc',
                'platform' => 'android',
            ]);

        $resp->assertOk();
        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'token' => 'fresh-fcm-token-abc',
            'platform' => 'android',
            'is_active' => true,
        ]);
        $this->assertEquals('fresh-fcm-token-abc', $user->fresh()->fcm_token,
            'legacy users.fcm_token kept in sync');
    }

    /** @test */
    public function repeated_registration_of_same_token_is_idempotent()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/device-token', ['token' => 'tok', 'platform' => 'ios'])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/device-token', ['token' => 'tok', 'platform' => 'ios'])
            ->assertOk();

        $this->assertEquals(1, DeviceToken::where('token', 'tok')->count());
    }

    /** @test */
    public function previously_deactivated_token_is_reactivated_on_re_registration()
    {
        $user = User::factory()->create();
        DeviceToken::create([
            'user_id' => $user->id,
            'token' => 'returning-tok',
            'platform' => 'android',
            'is_active' => false,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/device-token', ['token' => 'returning-tok'])
            ->assertOk();

        $this->assertTrue(DeviceToken::where('token', 'returning-tok')->first()->is_active);
    }

    /** @test */
    public function token_belonging_to_another_user_is_reassigned()
    {
        // Phone shared between roommates; user B logs in after user A logs out.
        // FCM gives both accounts the same registration token. We must reassign
        // — leaving it on user A would deliver user B's notifications to A.
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        DeviceToken::create([
            'user_id' => $userA->id,
            'token' => 'shared-handset-tok',
            'platform' => 'android',
            'is_active' => true,
        ]);

        $this->actingAs($userB, 'sanctum')
            ->postJson('/api/v1/auth/device-token', ['token' => 'shared-handset-tok'])
            ->assertOk();

        $row = DeviceToken::where('token', 'shared-handset-tok')->first();
        $this->assertEquals($userB->id, $row->user_id, 'token reassigned to current caller');
        $this->assertEquals(1, DeviceToken::where('token', 'shared-handset-tok')->count(),
            'no duplicate row created');
    }

    /** @test */
    public function multiple_devices_per_user_create_separate_rows()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/device-token', ['token' => 'phone-tok', 'platform' => 'android'])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/device-token', ['token' => 'tablet-tok', 'platform' => 'android'])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/device-token', ['token' => 'web-tok', 'platform' => 'web'])
            ->assertOk();

        $this->assertEquals(3, DeviceToken::where('user_id', $user->id)->count());
    }

    /** @test */
    public function platform_is_optional_and_defaults_to_unknown()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/device-token', ['token' => 'no-platform'])
            ->assertOk();

        $this->assertEquals('unknown',
            DeviceToken::where('token', 'no-platform')->first()->platform);
    }

    /** @test */
    public function platform_is_validated_against_allowed_values()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/device-token', ['token' => 'tok', 'platform' => 'wii'])
            ->assertStatus(422);
    }

    /** @test */
    public function unauthenticated_request_is_rejected()
    {
        $this->postJson('/api/v1/auth/device-token', ['token' => 'tok'])
            ->assertStatus(401);
    }
}
