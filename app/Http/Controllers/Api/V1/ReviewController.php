<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\SosRequest;
use App\Models\VetProfile;
use App\Services\ReviewService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ReviewService $reviewService
    ) {}

    /**
     * Create a review for a completed appointment.
     * POST /api/v1/reviews
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'appointment_uuid' => 'required_without:sos_uuid|string',
            'sos_uuid'         => 'required_without:appointment_uuid|string',
            'rating'           => 'required|integer|min:1|max:5',
            'comment'          => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        $appointmentId = null;
        $sosRequestId = null;
        $vetProfileId = null;

        if ($request->filled('appointment_uuid')) {
            $appointment = Appointment::where('uuid', $request->appointment_uuid)->first();
            if (!$appointment) {
                return $this->notFound('Appointment not found');
            }
            if ($appointment->user_id !== $user->id) {
                return $this->forbidden('You can only review your own appointments.');
            }
            // CRIT-02: Only completed appointments can be reviewed
            if ($appointment->status !== 'completed') {
                return $this->error('Reviews can only be submitted for completed appointments.', null, 422);
            }
            $appointmentId = $appointment->id;
            $vetProfileId = $appointment->vet_profile_id;
        } else {
            $sosRequest = SosRequest::where('uuid', $request->sos_uuid)->first();
            if (!$sosRequest) {
                return $this->notFound('SOS request not found');
            }
            if ($sosRequest->user_id !== $user->id) {
                return $this->forbidden('You can only review your own SOS requests.');
            }
            // CRIT-02: Only completed SOS requests can be reviewed
            if (!in_array($sosRequest->status, ['completed', 'sos_completed'])) {
                return $this->error('Reviews can only be submitted for completed SOS requests.', null, 422);
            }
            $sosRequestId = $sosRequest->id;
            $vetProfileId = $sosRequest->assigned_vet_id;
        }

        if (!$vetProfileId) {
            return $this->error('No vet was assigned to this request.', null, 422);
        }

        try {
            $review = $this->reviewService->create(
                userId: $user->id,
                vetProfileId: $vetProfileId,
                appointmentId: $appointmentId,
                data: [
                    'rating' => (int) $request->rating,
                    'comment' => $request->comment,
                ],
                sosRequestId: $sosRequestId,
            );

            return $this->created('Review submitted successfully', ['review' => $review]);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }

    /**
     * Get reviews for a vet.
     * GET /api/v1/reviews/vet/{uuid}
     */
    public function forVet(Request $request, string $uuid): JsonResponse
    {
        $vetProfile = VetProfile::where('uuid', $uuid)->first();

        if (!$vetProfile) {
            return $this->notFound('Vet not found');
        }

        $perPage = min((int) ($request->per_page ?? 15), 50);

        $reviews = $this->reviewService->getVetReviews($vetProfile->id, $perPage);

        return $this->success('Reviews retrieved', [
            'reviews' => $reviews->items(),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page'    => $reviews->lastPage(),
                'per_page'     => $reviews->perPage(),
                'total'        => $reviews->total(),
            ],
            'avg_rating' => $vetProfile->avg_rating,
            'total_reviews' => $vetProfile->total_reviews,
        ]);
    }

    /**
     * Vet replies to a review.
     * PUT /api/v1/reviews/{uuid}/reply
     */
    public function reply(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'reply' => 'required|string|max:1000',
        ]);

        $review = \App\Models\Review::where('uuid', $uuid)->first();

        if (!$review) {
            return $this->notFound('Review not found');
        }

        $user = $request->user();
        $vetProfile = VetProfile::where('user_id', $user->id)->first();

        if (!$vetProfile || $review->vet_profile_id !== $vetProfile->id) {
            return $this->forbidden('You can only reply to reviews for your profile.');
        }

        try {
            $review = $this->reviewService->vetReply($review, $request->reply);

            return $this->success('Reply added', ['review' => $review]);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }

    /**
     * Flag a review (user or admin).
     * PUT /api/v1/reviews/{uuid}/flag
     */
    public function flag(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500', // MED-06: Reason is now required
        ]);

        // MED-03: Only vets (for their own reviews) and admins can flag
        $user = $request->user();
        if (!$user->isVet() && !$user->isAdmin()) {
            return $this->forbidden('Only vets and admins can flag reviews.');
        }

        $review = \App\Models\Review::where('uuid', $uuid)->first();

        if (!$review) {
            return $this->notFound('Review not found');
        }

        // If vet, must be for their own profile
        if ($user->isVet()) {
            $vetProfile = VetProfile::where('user_id', $user->id)->first();
            if (!$vetProfile || $review->vet_profile_id !== $vetProfile->id) {
                return $this->forbidden('You can only flag reviews for your own profile.');
            }
        }

        try {
            $review = $this->reviewService->flagReview($review, $request->reason, $user->id);

            return $this->success('Review flagged for moderation', ['review' => $review]);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }
}
