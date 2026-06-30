<?php

namespace App\Services;

use App\Models\Review;
use App\Models\VetProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    public function __construct(private AuditService $auditService) {}

    /**
     * Create a review for a vet after a completed appointment, SOS, or consultation.
     */
    public function create(
        int $userId,
        int $vetProfileId,
        ?int $appointmentId,
        array $data,
        ?int $sosRequestId = null,
        ?int $consultationSessionId = null,
    ): Review {
        return DB::transaction(function () use ($userId, $vetProfileId, $appointmentId, $data, $sosRequestId, $consultationSessionId) {
            if ($appointmentId && Review::where('user_id', $userId)->where('appointment_id', $appointmentId)->exists()) {
                throw new \DomainException('You have already reviewed this appointment.');
            }

            if ($sosRequestId && Review::where('user_id', $userId)->where('sos_request_id', $sosRequestId)->exists()) {
                throw new \DomainException('You have already reviewed this SOS request.');
            }

            if ($consultationSessionId && Review::where('user_id', $userId)->where('consultation_session_id', $consultationSessionId)->exists()) {
                throw new \DomainException('You have already reviewed this consultation.');
            }

            $review = Review::create([
                'user_id'                => $userId,
                'vet_profile_id'         => $vetProfileId,
                'appointment_id'         => $appointmentId,
                'sos_request_id'         => $sosRequestId,
                'consultation_session_id' => $consultationSessionId,
                'rating'                 => $data['rating'],
                'comment'                => $data['comment'] ?? null,
            ]);

            $this->recalculateVetRating($vetProfileId);

            return $review->load(['user:id,name']);
        });
    }

    /**
     * Vet replies to a review.
     */
    public function vetReply(Review $review, string $reply): Review
    {
        $review->update([
            'vet_reply' => $reply,
            'vet_replied_at' => now(),
        ]);

        return $review;
    }

    /**
     * Flag a suspicious review.
     */
    public function flagReview(Review $review, string $reason, int $adminId): Review
    {
        $review->update([
            'is_flagged' => true,
            'flag_reason' => $reason,
        ]);

        $this->auditService->log(
            $adminId,
            Review::class,
            $review->id,
            'flagged',
            null,
            ['flag_reason' => $reason],
            "Review flagged: {$reason}"
        );

        return $review;
    }

    /**
     * Get reviews received by the authenticated vet (their own profile).
     */
    public function getMyReceivedReviews(int $vetProfileId, int $perPage = 15): LengthAwarePaginator
    {
        return Review::forVet($vetProfileId)
            ->with(['user:id,name,avatar', 'appointment:id,uuid,scheduled_at', 'consultationSession:id,uuid'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Get reviews for a vet with pagination.
     */
    public function getVetReviews(int $vetProfileId, int $perPage = 15): LengthAwarePaginator
    {
        return Review::forVet($vetProfileId)
            ->notFlagged()
            ->with(['user:id,name,avatar', 'appointment:id,uuid,scheduled_at'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Recalculate and update vet rating.
     */
    private function recalculateVetRating(int $vetProfileId): void
    {
        $stats = Review::where('vet_profile_id', $vetProfileId)
            ->notFlagged()
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as review_count')
            ->first();

        VetProfile::where('id', $vetProfileId)->update([
            'avg_rating' => $stats->avg_rating ? round($stats->avg_rating, 2) : null,
            'total_reviews' => $stats->review_count ?? 0,
        ]);
    }
}
