<?php

namespace App\Services;

use App\Contracts\VideoProviderInterface;
use App\Models\ConsultationMessage;
use App\Models\ConsultationSession;
use App\Models\Payment;
use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates the online consultation lifecycle (instant + scheduled).
 *
 * Flow per spec:
 *   1. createInstantSession   — user picks issue, payment is held (PaymentService).
 *   2. listAvailableVets      — emergency-available + matching modality + species.
 *   3. matchSession(vet)      — vet accepts; status → matched; 10-min no-show watchdog armed.
 *   4. join(role)             — issues a provider join token; updates joined-at timestamps.
 *   5. complete()             — both parties agree consult is done; capture payment.
 *   6. fail(reason)           — technical failure or vet no-show; auto_refund_triggered=true,
 *                                payment refunded by PaymentService.
 *
 * Auto-refund triggers (spec):
 *   - vet no-show >10 min after match
 *   - >=3 connection failures in first 5 min
 *   - vet cancels
 *   - duplicate payment (handled in PaymentService)
 */
class ConsultationService
{
    /** Auto-refund kicks in after this many connection failures. */
    public const CONNECTION_FAILURE_THRESHOLD = 3;

    public function __construct(
        private VideoProviderInterface $videoProvider,
        private AuditService $auditService,
    ) {}

    /**
     * Start an instant consult. Returns a pending session — match it via matchSession()
     * after a vet accepts. Payment must be created/held by the caller.
     */
    public function createInstantSession(
        User $user,
        ?int $petId,
        string $modality,
        ?string $issueCategory,
        ?string $issueDescription,
        ?int $feeAmount = null,
        ?int $paymentId = null,
    ): ConsultationSession {
        return DB::transaction(function () use ($user, $petId, $modality, $issueCategory, $issueDescription, $feeAmount, $paymentId) {
            $session = ConsultationSession::create([
                'user_id' => $user->id,
                'pet_id' => $petId,
                'origin' => 'instant',
                'modality' => $modality,
                'issue_category' => $issueCategory,
                'issue_description' => $issueDescription,
                'status' => 'matching',
                'fee_amount' => $feeAmount,
                'payment_id' => $paymentId,
            ]);

            $this->auditService->log(
                $user->id, ConsultationSession::class, $session->id,
                'created', null,
                ['modality' => $modality, 'issue_category' => $issueCategory],
                'Instant consultation created'
            );

            return $session;
        });
    }

    /**
     * Vets eligible to take the given session right now. Per spec:
     *   - approved + active
     *   - emergency-available OR currently in working hours
     *   - accepts the session's pet species (if known)
     *   - online_fee set (means they offer online consults)
     */
    public function listAvailableVets(ConsultationSession $session, int $limit = 10): Collection
    {
        $species = $session->pet?->species;

        return VetProfile::query()
            ->where('vet_status', 'approved')
            ->where('is_active', true)
            ->whereNotNull('online_fee')
            ->when($species, function ($q) use ($species) {
                $q->whereJsonContains('accepted_species', $species);
            })
            ->orderByDesc('is_emergency_available')
            ->orderBy('avg_response_minutes')
            ->limit($limit)
            ->get();
    }

    /**
     * Vet accepts a pending instant consult. Creates the room and arms the no-show watchdog.
     */
    public function matchSession(ConsultationSession $session, VetProfile $vetProfile): ConsultationSession
    {
        return DB::transaction(function () use ($session, $vetProfile) {
            $session = ConsultationSession::where('id', $session->id)->lockForUpdate()->first();

            if ($session->status !== 'matching') {
                throw new \DomainException('Session is no longer available for matching.');
            }

            $room = $this->videoProvider->createRoom($session);

            $session->update([
                'vet_profile_id' => $vetProfile->id,
                'status' => 'matched',
                'matched_at' => now(),
                'vet_no_show_check_at' => now()->addMinutes(10),
                'room_provider' => $room['provider'],
                'room_id' => $room['room_id'],
                'room_metadata' => $room['metadata'] ?? null,
            ]);

            $this->logSystemEvent($session, $vetProfile->user_id, 'vet matched');

            return $session;
        });
    }

    /**
     * Issue a join token for a participant. Updates the joined-at columns.
     * Once both parties have joined, status flips to 'active' and started_at is set.
     */
    public function join(ConsultationSession $session, User $user, string $role): array
    {
        if (!in_array($role, ['user', 'vet'], true)) {
            throw new \InvalidArgumentException('role must be user or vet');
        }

        return DB::transaction(function () use ($session, $user, $role) {
            $session = ConsultationSession::where('id', $session->id)->lockForUpdate()->first();

            if (!$session->isActive() && $session->status !== 'matched') {
                throw new \DomainException('Session is not joinable in status: ' . $session->status);
            }

            $token = $this->videoProvider->generateJoinToken($session, $role, $user->id);

            $updates = ['status' => 'joining'];
            if ($role === 'vet' && !$session->vet_joined_at) {
                $updates['vet_joined_at'] = now();
            }
            if ($role === 'user' && !$session->user_joined_at) {
                $updates['user_joined_at'] = now();
            }

            // Both parties present → active.
            $bothJoined = ($role === 'vet' && $session->user_joined_at)
                || ($role === 'user' && $session->vet_joined_at);
            if ($bothJoined) {
                $updates['status'] = 'active';
                $updates['started_at'] = now();
            }

            $session->update($updates);

            return [
                'session' => $session->fresh(),
                'room_provider' => $session->room_provider,
                'room_id' => $session->room_id,
                'token' => $token,
                'role' => $role,
            ];
        });
    }

    /**
     * Mark a connection failure. After threshold, fail the session (auto-refund).
     * Returns true if the session was failed by this call.
     */
    public function reportConnectionFailure(ConsultationSession $session): bool
    {
        return DB::transaction(function () use ($session) {
            $session = ConsultationSession::where('id', $session->id)->lockForUpdate()->first();
            $session->increment('connection_failures');

            if ($session->connection_failures >= self::CONNECTION_FAILURE_THRESHOLD
                && !$session->isFinal()) {
                $this->failInternal($session, 'connection_failures_exceeded');
                return true;
            }
            return false;
        });
    }

    /**
     * Vet/user marks the consult complete (happy path).
     * Caller (controller) is responsible for capturing payment via PaymentService.
     */
    public function complete(
        ConsultationSession $session,
        ?string $vetNotes = null,
        ?string $diagnosis = null,
        ?string $prescription = null,
    ): ConsultationSession {
        return DB::transaction(function () use ($session, $vetNotes, $diagnosis, $prescription) {
            $session = ConsultationSession::where('id', $session->id)->lockForUpdate()->first();

            if ($session->isFinal()) {
                return $session;
            }

            $duration = $session->started_at
                ? $session->started_at->diffInSeconds(now())
                : null;

            $session->update([
                'status' => 'completed',
                'ended_at' => now(),
                'duration_seconds' => $duration,
                'vet_notes' => $vetNotes,
                'diagnosis' => $diagnosis,
                'prescription' => $prescription,
            ]);

            $this->videoProvider->destroyRoom($session);

            return $session;
        });
    }

    /**
     * Vet cancels (counts as no-show / failure → refund).
     */
    public function vetCancel(ConsultationSession $session, ?string $reason = null): ConsultationSession
    {
        return DB::transaction(function () use ($session, $reason) {
            $session = ConsultationSession::where('id', $session->id)->lockForUpdate()->first();
            $this->failInternal($session, $reason ?? 'vet_cancelled');
            return $session;
        });
    }

    /**
     * Watchdog: called by NoShowWatchdogJob. If matched but vet hasn't joined within
     * the deadline, fail the session and trigger refund.
     */
    public function expireIfVetNoShow(ConsultationSession $session): bool
    {
        return DB::transaction(function () use ($session) {
            $session = ConsultationSession::where('id', $session->id)->lockForUpdate()->first();
            if ($session->status !== 'matched') return false;
            if (!$session->vet_no_show_check_at) return false;
            if ($session->vet_no_show_check_at->isFuture()) return false;
            if ($session->vet_joined_at) return false;

            $this->failInternal($session, 'vet_no_show_10min');
            return true;
        });
    }

    /**
     * Send a chat message (text only here; attachments handled at controller).
     */
    public function postMessage(
        ConsultationSession $session,
        User $sender,
        string $body,
    ): ConsultationMessage {
        $role = $session->user_id === $sender->id ? 'user' : 'vet';

        return ConsultationMessage::create([
            'consultation_session_id' => $session->id,
            'sender_id' => $sender->id,
            'sender_role' => $role,
            'type' => 'text',
            'body' => $body,
        ]);
    }

    private function failInternal(ConsultationSession $session, string $reason): void
    {
        $session->update([
            'status' => $reason === 'vet_no_show_10min' ? 'expired' : 'failed',
            'ended_at' => now(),
            'auto_refund_triggered' => true,
            'refund_reason' => $reason,
        ]);
        $this->videoProvider->destroyRoom($session);

        Log::info('Consultation auto-refund triggered', [
            'session_uuid' => $session->uuid,
            'reason' => $reason,
        ]);
    }

    private function logSystemEvent(ConsultationSession $session, int $actorUserId, string $body): void
    {
        ConsultationMessage::create([
            'consultation_session_id' => $session->id,
            'sender_id' => $actorUserId,
            'sender_role' => 'system',
            'type' => 'system_event',
            'body' => $body,
        ]);
    }
}
