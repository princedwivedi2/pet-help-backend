<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ConsultationMessage;
use App\Models\ConsultationSession;
use App\Models\Pet;
use App\Models\VetProfile;
use App\Services\ConsultationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'pet_uuid' => 'nullable|string|exists:pets,uuid',
            'modality' => 'required|in:video,audio,chat',
            'issue_category' => 'nullable|string|max:80',
            'issue_description' => 'nullable|string|max:2000',
            'fee_amount' => 'nullable|integer|min:0',
            'payment_uuid' => 'nullable|string',
        ]);

        $user = $request->user();
        $petId = null;
        if (!empty($data['pet_uuid'])) {
            $pet = Pet::where('uuid', $data['pet_uuid'])->first();
            if (!$pet || $pet->user_id !== $user->id) {
                return $this->forbidden('Pet does not belong to you.');
            }
            $petId = $pet->id;
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
        $session = ConsultationSession::with(['vetProfile:id,uuid,clinic_name,vet_name', 'pet:id,uuid,name'])
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
        $query = ConsultationSession::query()->orderByDesc('created_at');

        if ($user->isVet()) {
            $vetProfile = VetProfile::where('user_id', $user->id)->first();
            $query->where('vet_profile_id', $vetProfile?->id);
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
     * POST /api/v1/consultations/{uuid}/complete   (vet ends + writes notes)
     */
    public function complete(Request $request, string $uuid): JsonResponse
    {
        $data = $request->validate([
            'vet_notes' => 'nullable|string|max:5000',
            'diagnosis' => 'nullable|string|max:2000',
            'prescription' => 'nullable|string|max:5000',
        ]);

        $session = ConsultationSession::where('uuid', $uuid)->first();
        if (!$session) return $this->notFound('Consultation not found');

        $user = $request->user();
        $vetProfile = VetProfile::where('user_id', $user->id)->first();
        if (!$vetProfile || $session->vet_profile_id !== $vetProfile->id) {
            return $this->forbidden('Only the assigned vet can complete this consultation.');
        }

        $session = $this->consultationService->complete(
            $session, $data['vet_notes'] ?? null, $data['diagnosis'] ?? null, $data['prescription'] ?? null
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
}
