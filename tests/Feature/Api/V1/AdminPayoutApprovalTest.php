<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\VetProfile;
use App\Models\VetWallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin Payout Approval Tests
 * Covers: POST /api/v1/admin/payouts/{vet_uuid}/process
 */
class AdminPayoutApprovalTest extends TestCase
{
    use RefreshDatabase;

    private string $prefix = '/api/v1/admin';
    private User $admin;
    private User $vetUser;
    private VetProfile $vetProfile;
    private VetWallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->vetUser = User::factory()->create(['role' => 'vet']);
        $this->vetProfile = VetProfile::factory()->verified()->create([
            'user_id' => $this->vetUser->id,
        ]);

        $this->wallet = VetWallet::create([
            'vet_profile_id' => $this->vetProfile->id,
            'balance'        => 10000,
            'total_earned'   => 10000,
            'total_paid_out' => 0,
            'pending_payout' => 5000,
        ]);
    }

    private function createPendingPayoutRequest(int $amount = 5000): WalletTransaction
    {
        return WalletTransaction::create([
            'vet_profile_id' => $this->vetProfile->id,
            'type'           => 'payout_request',
            'amount'         => $amount,
            'balance_after'  => $this->wallet->balance,
            'status'         => 'pending',
            'description'    => 'Payout request — bank transfer',
        ]);
    }

    // ─── Success paths ───────────────────────────────────────────────

    public function test_admin_can_process_payout_request(): void
    {
        $this->createPendingPayoutRequest(5000);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("{$this->prefix}/payouts/{$this->vetProfile->uuid}/process", [
                'notes' => 'Processed via bank transfer',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => ['payout_amount', 'new_balance', 'transaction'],
            ]);

        // Wallet balance should be debited
        $this->assertDatabaseHas('vet_wallets', [
            'vet_profile_id' => $this->vetProfile->id,
            'balance'        => 5000, // 10000 - 5000
            'total_paid_out' => 5000,
        ]);

        // A payout_completed transaction should exist
        $this->assertDatabaseHas('wallet_transactions', [
            'vet_profile_id' => $this->vetProfile->id,
            'type'           => 'payout_completed',
            'amount'         => 5000,
            'status'         => 'completed',
        ]);
    }

    public function test_original_payout_request_marked_completed(): void
    {
        $tx = $this->createPendingPayoutRequest(5000);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("{$this->prefix}/payouts/{$this->vetProfile->uuid}/process");

        $this->assertDatabaseHas('wallet_transactions', [
            'id'     => $tx->id,
            'status' => 'completed',
        ]);
    }

    public function test_process_without_notes_still_works(): void
    {
        $this->createPendingPayoutRequest(5000);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("{$this->prefix}/payouts/{$this->vetProfile->uuid}/process");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    // ─── Idempotency / guard paths ───────────────────────────────────

    public function test_cannot_process_payout_twice(): void
    {
        $this->createPendingPayoutRequest(5000);

        // First call
        $this->actingAs($this->admin, 'sanctum')
            ->postJson("{$this->prefix}/payouts/{$this->vetProfile->uuid}/process")
            ->assertOk();

        // Second call — no pending payout_request remains
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("{$this->prefix}/payouts/{$this->vetProfile->uuid}/process");

        $response->assertStatus(422);
    }

    public function test_returns_422_when_no_pending_payout_request(): void
    {
        // Wallet has pending_payout but no payout_request transaction
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("{$this->prefix}/payouts/{$this->vetProfile->uuid}/process");

        $response->assertStatus(422);
    }

    public function test_returns_422_when_pending_payout_is_zero(): void
    {
        // Wallet has no pending amount
        $this->wallet->update(['pending_payout' => 0]);

        $this->createPendingPayoutRequest(5000);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("{$this->prefix}/payouts/{$this->vetProfile->uuid}/process");

        $response->assertStatus(422);
    }

    public function test_returns_404_for_unknown_vet(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("{$this->prefix}/payouts/nonexistent-uuid/process");

        $response->assertStatus(404);
    }

    // ─── Access control ──────────────────────────────────────────────

    public function test_non_admin_cannot_process_payout(): void
    {
        $this->createPendingPayoutRequest(5000);

        $response = $this->actingAs($this->vetUser, 'sanctum')
            ->postJson("{$this->prefix}/payouts/{$this->vetProfile->uuid}/process");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_process_payout(): void
    {
        $this->createPendingPayoutRequest(5000);

        $this->postJson("{$this->prefix}/payouts/{$this->vetProfile->uuid}/process")
            ->assertStatus(401);
    }
}
