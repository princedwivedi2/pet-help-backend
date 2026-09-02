<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ConsultationMessage;
use App\Models\ConsultationSession;
use App\Models\VetProfile;
use App\Services\ConsultationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Peterujah\Agora\Agora;
use Peterujah\Agora\Builders\RtcToken;
use Peterujah\Agora\Roles;
use Peterujah\Agora\User as AgoraUser;

class ConsultationController extends Controller
{
    use ApiResponse;

    public function __construct(private ConsultationService $consultationService) {}

    /**
     * POST /api/v1/consultations
     * User starts an instant consult.
     */
    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pet_id' => [
                'nullable',
                'integer',
                Rule::exists('pets', 'id')
                    ->where('user_id', $request->user()->id)
                    ->whereNull('deleted_at'),
            ],
            'modality' => 'required|in:video,audio,chat',
            'issue_category' => 'nullable|string|max:80',
            'issue_description' => 'nullable|string|max:2000',
            'fee_amount' => 'nullable|integer|min:0',
            'payment_uuid' => 'nullable|string',
            'vet_uuid' => 'nullable|string|exists:vet_profiles,uuid',
        ]);

        $user = $request->user();
        $petId = $data['pet_id'] ?? null;

        // CRIT-01 / D-01: validate fee_amount against vet's stored consultation_fee
        if (!empty($data['vet_uuid']) && isset($data['fee_amount'])) {
            $vetProfile = \App\Models\VetProfile::where('uuid', $data['vet_uuid'])->first();
            if ($vetProfile && (int) $data['fee_amount'] !== (int) $vetProfile->consultation_fee) {
                return $this->error(
                    'Fee amount does not match the vet\'s consultation fee.',
                    ['fee_amount' => ['Expected ' . $vetProfile->consultation_fee]],
                    422
                );
            }
        }

        $paymentId = null;
        if (!empty($data['payment_uuid'])) {
            $payment = \App\Models\Payment::where('uuid', $data['payment_uuid'])
                ->where('user_id', $user->id)->first();
            $paymentId = $payment?->id;
        }

        $session = $this->consultationService->createInstantSession(
            user: $user,
            petId: $petId,
            modality: $data['modality'],
            issueCategory: $data['issue_category'] ?? null,
            issueDescription: $data['issue_description'] ?? null,
            feeAmount: $data['fee_amount'] ?? null,
            paymentId: $paymentId,
        );

        return $this->created('Consultation request created', [
            'consultation' => $session,
            'available_vets' => $this->consultationService->listAvailableVets($session),
        ]);
    }

    /**
     * GET /api/v1/consultations/{uuid}
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        $session = ConsultationSession::with(['vetProfile:id,uuid,clinic_name,vet_name', 'pet:id,name'])
            ->where('uuid', $uuid)->first();
        if (!$session) return $this->notFound('Consultation not found');
        if (!$this->canAccess($request->user(), $session)) return $this->forbidden('Access denied');

        return $this->success('Consultation retrieved', ['consultation' => $session]);
    }

    /**
     * GET /api/v1/consultations
     * List the caller's consultations (user sees own; vet sees own).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min((int) ($request->per_page ?? 15), 50);
        $query = ConsultationSession::query()->with(['user:id,name', 'pet:id,name'])->orderByDesc('created_at');

        if ($user->isVet()) {
            $vetProfile = VetProfile::where('user_id', $user->id)->first();
            if (!$vetProfile) {
                return $this->success('Consultations retrieved', [
                    'consultations' => [],
                    'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => $perPage, 'total' => 0],
                ]);
            }
            $query->where('vet_profile_id', $vetProfile->id);
        } else {
            $query->where('user_id', $user->id);
        }

        $page = $query->paginate($perPage);
        return $this->success('Consultations retrieved', [
            'consultations' => $page->items(),
            'pagination' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/consultations/available   (vet)
     *
     * Unassigned instant video/audio requests this vet is eligible to pick up —
     * i.e. still in the "matching" broadcast window (see
     * ConsultationService::listAvailableVets for the matching eligibility rules
     * this mirrors: approved + active + online_fee set + species match).
     * Chat is excluded — chat consultations are handled by the AI assistant,
     * not broadcast to vets.
     */
    public function availableForVet(Request $request): JsonResponse
    {
        $vetProfile = VetProfile::where('user_id', $request->user()->id)->first();
        if (!$vetProfile || !$vetProfile->isApproved()) {
            return $this->forbidden('Only approved vets can view available consultations.');
        }

        $sessions = ConsultationSession::query()
            ->where('status', 'matching')
            ->whereNull('vet_profile_id')
            ->whereIn('modality', ['video', 'audio'])
            ->when($vetProfile->accepted_species, function ($q) use ($vetProfile) {
                $q->where(function ($q2) use ($vetProfile) {
                    $q2->whereNull('pet_id')
                        ->orWhereHas('pet', function ($petQuery) use ($vetProfile) {
                            $petQuery->whereIn('species', $vetProfile->accepted_species);
                        });
                });
            })
            ->with(['user:id,name', 'pet:id,name,species'])
            ->orderBy('created_at')
            ->limit(20)
            ->get();

        return $this->success('Available consultations retrieved', ['consultations' => $sessions]);
    }

    /**
     * POST /api/v1/consultations/{uuid}/accept   (vet)
     */
    public function accept(Request $request, string $uuid): JsonResponse
    {
        $session = ConsultationSession::where('uuid', $uuid)->first();
        if (!$session) return $this->notFound('Consultation not found');

        $vetProfile = VetProfile::where('user_id', $request->user()->id)->first();
        if (!$vetProfile || !$vetProfile->isApproved()) {
            return $this->forbidden('Only approved vets can accept consultations.');
        }

        try {
            $session = $this->consultationService->matchSession($session, $vetProfile);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->success('Consultation accepted', ['consultation' => $session]);
    }

    /**
     * POST /api/v1/consultations/{uuid}/join
     * Issues a provider join token. Caller must already be a participant.
     */
    public function join(Request $request, string $uuid): JsonResponse
    {
        $session = ConsultationSession::where('uuid', $uuid)->first();
        if (!$session) return $this->notFound('Consultation not found');

        $user = $request->user();
        $role = null;
        if ($user->id === $session->user_id) $role = 'user';
        if ($user->isVet()) {
            $vetProfile = VetProfile::where('user_id', $user->id)->first();
            if ($vetProfile && $session->vet_profile_id === $vetProfile->id) $role = 'vet';
        }
        if (!$role) return $this->forbidden('You are not a participant in this consultation.');

        // CRIT-02 / D-02: gate join on payment status when a payment is attached
        if ($session->payment_id !== null) {
            $payment = \App\Models\Payment::find($session->payment_id);
            if (!$payment || $payment->payment_status !== 'paid') {
                return $this->error('Payment has not been completed for this consultation.', null, 422);
            }
        }

        try {
            $payload = $this->consultationService->join($session, $user, $role);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->success('Join token issued', $payload);
    }

    /**
     * POST /api/v1/consultations/{uuid}/connection-failure
     * Client reports a connection drop. After threshold, session is auto-failed + refunded.
     */
    public function connectionFailure(Request $request, string $uuid): JsonResponse
    {
        $session = ConsultationSession::where('uuid', $uuid)->first();
        if (!$session) return $this->notFound('Consultation not found');
        if (!$this->canAccess($request->user(), $session)) return $this->forbidden('Access denied');

        $failed = $this->consultationService->reportConnectionFailure($session);
        return $this->success(
            $failed ? 'Connection failure threshold reached — session failed' : 'Connection failure recorded',
            ['consultation' => $session->fresh()]
        );
    }

    /**
     * POST /api/v1/consultations/{uuid}/complete
     * Either participant (user or vet) may end the call.
     * Vet-supplied clinical fields (notes, diagnosis, prescription) are accepted
     * but not required — the user side simply sends no body.
     */
    public function complete(Request $request, string $uuid): JsonResponse
    {
        $data = $request->validate([
            'vet_notes'    => 'nullable|string|max:5000',
            'diagnosis'    => 'nullable|string|max:2000',
            'prescription' => 'nullable|string|max:5000',
        ]);

        $session = ConsultationSession::where('uuid', $uuid)->first();
        if (!$session) return $this->notFound('Consultation not found');

        $user       = $request->user();
        $isOwner    = $user->id === $session->user_id;
        $isVet      = false;
        if ($user->isVet()) {
            $vetProfile = VetProfile::where('user_id', $user->id)->first();
            $isVet = $vetProfile && $session->vet_profile_id === $vetProfile->id;
        }

        if (!$isOwner && !$isVet) {
            return $this->forbidden('Only a participant of this consultation can complete it.');
        }

        $session = $this->consultationService->complete(
            $session,
            $data['vet_notes']    ?? null,
            $data['diagnosis']    ?? null,
            $data['prescription'] ?? null,
        );

        return $this->success('Consultation completed', ['consultation' => $session]);
    }

    /**
     * POST /api/v1/consultations/{uuid}/cancel   (vet)
     */
    public function vetCancel(Request $request, string $uuid): JsonResponse
    {
        $data = $request->validate(['reason' => 'nullable|string|max:500']);

        $session = ConsultationSession::where('uuid', $uuid)->first();
        if (!$session) return $this->notFound('Consultation not found');

        $vetProfile = VetProfile::where('user_id', $request->user()->id)->first();
        if (!$vetProfile || $session->vet_profile_id !== $vetProfile->id) {
            return $this->forbidden('Only the assigned vet can cancel this consultation.');
        }

        $session = $this->consultationService->vetCancel($session, $data['reason'] ?? null);
        return $this->success('Consultation cancelled', ['consultation' => $session]);
    }

    /**
     * GET /api/v1/consultations/{uuid}/messages
     */
    public function messages(Request $request, string $uuid): JsonResponse
    {
        $session = ConsultationSession::where('uuid', $uuid)->first();
        if (!$session) return $this->notFound('Consultation not found');
        if (!$this->canAccess($request->user(), $session)) return $this->forbidden('Access denied');

        $messages = ConsultationMessage::where('consultation_session_id', $session->id)
            ->orderBy('created_at')->get();

        return $this->success('Messages retrieved', ['messages' => $messages]);
    }

    /**
     * POST /api/v1/consultations/{uuid}/messages
     */
    public function postMessage(Request $request, string $uuid): JsonResponse
    {
        $data = $request->validate([
            'body' => 'required|string|max:4000',
        ]);

        $session = ConsultationSession::where('uuid', $uuid)->first();
        if (!$session) return $this->notFound('Consultation not found');
        if (!$this->canAccess($request->user(), $session)) return $this->forbidden('Access denied');
        if ($session->isFinal()) return $this->error('Consultation has ended.', null, 422);

        $message = $this->consultationService->postMessage($session, $request->user(), $data['body']);
        return $this->created('Message posted', ['message' => $message]);
    }

    private function canAccess($user, ConsultationSession $session): bool
    {
        if ($user->id === $session->user_id) return true;
        if ($user->isAdmin()) return true;
        if ($user->isVet()) {
            $vetProfile = VetProfile::where('user_id', $user->id)->first();
            return $vetProfile && $session->vet_profile_id === $vetProfile->id;
        }
        return false;
    }

    /**
     * GET /api/v1/consultations/{uuid}/rtc-token
     * Generate an Agora RTC token for a consultation participant (user OR vet).
     *
     * Both the pet owner (patient side) and the assigned vet may request a token.
     * Each receives their own user_id as uid so Agora can distinguish the streams.
     * Returns 403 for completed sessions or callers not in this consultation.
     */
    public function rtcToken(Request $request, ConsultationSession $consultation): JsonResponse
    {
        $user = $request->user();

        // Determine whether the caller is the patient or the assigned vet.
        $isOwner = $user->id === $consultation->user_id;
        $isAssignedVet = false;
        if ($user->isVet()) {
            $vetProfile = VetProfile::where('user_id', $user->id)->first();
            $isAssignedVet = $vetProfile && $consultation->vet_profile_id === $vetProfile->id;
        }

        if (!$isOwner && !$isAssignedVet) {
            return $this->forbidden('Unauthorized');
        }

        if ($consultation->status === 'completed') {
            return $this->forbidden('Unauthorized');
        }

        // Record this as the participant joining (enforces the scheduled join
        // window for slot-booked consults, and flips the session to
        // joining/active once both sides have fetched a token).
        try {
            $this->consultationService->markJoined($consultation, $isAssignedVet ? 'vet' : 'user');
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        $ttl     = 3600; // 1 hour
        $channel = 'consultation_' . str_replace('-', '', $consultation->uuid);
        $uid     = $user->id;

        $client = new Agora(
            config('services.agora.app_id'),
            config('services.agora.app_certificate'),
        );
        $client->setExpiration($ttl);

        $agoraUser = (new AgoraUser($uid))
            ->setPrivilegeExpire($ttl)
            ->setChannel($channel)
            ->setRole(Roles::RTC_PUBLISHER);

        $token = RtcToken::buildTokenWithUid($client, $agoraUser);

        return $this->success('Token generated', [
            'token'      => $token,
            'uid'        => $uid,
            'channel'    => $channel,
            'expires_at' => now()->addSeconds($ttl)->toIso8601String(),
        ]);
    }
}
