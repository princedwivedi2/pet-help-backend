<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use App\Models\VetProfile;
use App\Notifications\AppointmentBookedNotification;
use App\Notifications\AppointmentStatusNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentService
{
    private const USER_CANCELLATION_CUTOFF_HOURS = 2;

    /**
     * Valid appointment status transitions.
     */
    private const VALID_TRANSITIONS = [
        'pending'          => ['accepted', 'rejected', 'cancelled_by_user', 'cancelled_by_vet', 'cancelled', 'confirmed'],
        'accepted'         => ['confirmed', 'in_progress', 'cancelled_by_user', 'cancelled_by_vet', 'cancelled'],
        'confirmed'        => ['in_progress', 'completed', 'cancelled_by_user', 'cancelled_by_vet', 'cancelled', 'no_show'],
        'rejected'         => [],
        'in_progress'      => ['completed', 'no_show'],
        'completed'        => [],
        'cancelled_by_user' => [],
        'cancelled_by_vet' => [],
        'cancelled'        => [],
        'no_show'          => [],
    ];

    public function __construct(
        private AuditService $auditService
    ) {}

    /**
     * Create a new appointment (with double-booking prevention).
     */
    public function create(User $user, VetProfile $vetProfile, array $data): Appointment
    {
        return DB::transaction(function () use ($user, $vetProfile, $data) {
            // Validate scheduled_at is in the future (MED-02)
            $scheduledAt = Carbon::parse($data['scheduled_at']);
            if ($scheduledAt->isPast()) {
                throw new \DomainException('Appointments cannot be scheduled in the past.');
            }

            $durationMinutes = $data['duration_minutes'] ?? 30;
            $newEnd = (clone $scheduledAt)->addMinutes($durationMinutes);

            // Lock the vet's appointments and check for time-range overlap (HIGH-01)
            $driver = DB::getDriverName();

            $conflict = Appointment::where('vet_profile_id', $vetProfile->id)
                ->whereIn('status', ['pending', 'accepted', 'confirmed', 'in_progress'])
                ->lockForUpdate()
                ->where(function ($q) use ($scheduledAt, $newEnd, $driver) {
                    // Existing appointment overlaps if: existing.start < new.end AND existing.end > new.start
                    if ($driver === 'sqlite') {
                        $q->whereRaw('scheduled_at < ?', [$newEnd])
                          ->whereRaw("julianday(scheduled_at, '+' || duration_minutes || ' minutes') > julianday(?)", [$scheduledAt]);
                    } else {
                        $q->where('scheduled_at', '<', $newEnd)
                          ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > ?', [$scheduledAt]);
                    }
                })
                ->exists();

            if ($conflict) {
                throw new \DomainException('This time slot overlaps with an existing booking.');
            }

            // Validate home visit has address
            $appointmentType = $data['appointment_type'] ?? 'clinic_visit';
            if ($appointmentType === 'home_visit' && empty($data['home_address'])) {
                throw new \DomainException('Home visit requires an address.');
            }

            // Validate appointment_type against vet's consultation_types
            if (!empty($vetProfile->consultation_types) && is_array($vetProfile->consultation_types)) {
                if (!in_array($appointmentType, $vetProfile->consultation_types)) {
                    throw new \DomainException("This veterinarian does not offer {$appointmentType} consultations.");
                }
            }

            // Determine fee based on appointment type
            $feeAmount = $vetProfile->consultation_fee;
            if ($appointmentType === 'home_visit' && $vetProfile->home_visit_fee) {
                $feeAmount = $vetProfile->home_visit_fee;
            }

            // Validate pet is required
            if (empty($data['pet_id'])) {
                throw new \DomainException('A pet must be selected for the appointment.');
            }

            // Validate pet belongs to this user
            if (!$user->pets()->where('id', $data['pet_id'])->exists()) {
                throw new \DomainException('The selected pet does not belong to you.');
            }

            $appointment = Appointment::create([
                'user_id'          => $user->id,
                'vet_profile_id'   => $vetProfile->id,
                'pet_id'           => $data['pet_id'],
                'scheduled_at'     => $data['scheduled_at'],
                'duration_minutes' => $data['duration_minutes'] ?? 30,
                'reason'           => $data['reason'],
                'notes'            => $data['notes'] ?? null,
                'appointment_type' => $appointmentType,
                'is_emergency'     => $data['is_emergency'] ?? false,
                'photo_url'        => $data['photo_url'] ?? null,
                'home_address'     => $data['home_address'] ?? null,
                'home_latitude'    => $data['home_latitude'] ?? null,
                'home_longitude'   => $data['home_longitude'] ?? null,
                'fee_amount'       => $feeAmount,
                'status'           => 'pending',
            ]);

            // Audit log
            $this->auditService->logStatusChange(
                $user->id, Appointment::class, $appointment->id, 'none', 'pending', 'Appointment created'
            );

            Log::info('Appointment created', [
                'appointment_uuid' => $appointment->uuid,
                'user_id'          => $user->id,
                'vet_profile_id'   => $vetProfile->id,
                'scheduled_at'     => $data['scheduled_at'],
                'type'             => $appointmentType,
            ]);

            // Notify the vet user
            try {
                $vetProfile->user?->notify(new AppointmentBookedNotification($appointment));
            } catch (\Throwable $e) {
                report($e);
            }

            return $appointment->load(['user:id,name', 'vetProfile:id,user_id,uuid,clinic_name,vet_name', 'pet:id,name,species']);
        });
    }

    /**
     * Confirm an appointment (vet action).
     */
    public function confirm(Appointment $appointment): Appointment
    {
        return $this->transitionStatus($appointment, 'confirmed', function ($appt) {
            $appt->update(['status' => 'confirmed']);
        });
    }

    /**
     * Accept an appointment (vet action).
     */
    public function accept(Appointment $appointment): Appointment
    {
        return $this->transitionStatus($appointment, 'accepted', function ($appt) {
            $appt->update(['status' => 'accepted', 'accepted_at' => now()]);
            // Increment total_appointments on acceptance, not on booking (HIGH-04)
            $appt->vetProfile?->increment('total_appointments');
        });
    }

    /**
     * Reject an appointment (vet action).
     */
    public function reject(Appointment $appointment, string $reason): Appointment
    {
        return $this->transitionStatus($appointment, 'rejected', function ($appt) use ($reason) {
            $appt->update(['status' => 'rejected', 'rejected_at' => now(), 'rejection_reason' => $reason]);
        });
    }

    /**
     * Complete an appointment.
     */
    public function complete(Appointment $appointment, ?string $notes = null): Appointment
    {
        return $this->transitionStatus($appointment, 'completed', function ($appt) use ($notes) {
            $updateData = [
                'status' => 'completed',
                'completed_at' => now(),
                'cancelled_at_slot_release' => now(),
            ];
            if ($notes) $updateData['notes'] = $notes;
            $appt->update($updateData);
            // completed_appointments is now incremented in transitionStatus (LOW-04)
        });
    }

    /**
     * Cancel an appointment.
     */
    public function cancel(Appointment $appointment, User $cancelledBy, string $reason): Appointment
    {
        if (trim($reason) === '') {
            throw new \DomainException('Cancellation reason is required.');
        }

        if ($cancelledBy->id === $appointment->user_id) {
            $hoursUntilAppointment = now()->diffInHours($appointment->scheduled_at, false);
            if ($hoursUntilAppointment < self::USER_CANCELLATION_CUTOFF_HOURS) {
                throw new \DomainException('Appointments can only be cancelled at least 2 hours before scheduled time.');
            }
        }

        $isUserCancel = $cancelledBy->id === $appointment->user_id;
        $status = $isUserCancel ? 'cancelled_by_user' : ($cancelledBy->isVet() ? 'cancelled_by_vet' : 'cancelled');

        return $this->transitionStatus($appointment, $status, function ($appt) use ($cancelledBy, $reason, $status) {
            $appt->update([
                'status' => $status,
                'cancellation_reason' => $reason,
                'cancelled_by' => $cancelledBy->id,
                'cancelled_at' => now(),
                'cancelled_at_slot_release' => now(),
            ]);
        });
    }

    /**
     * Mark an appointment as no-show (vet action).
     */
    public function markNoShow(Appointment $appointment): Appointment
    {
        return $this->transitionStatus($appointment, 'no_show', function ($appt) {
            $appt->update([
                'status' => 'no_show',
                'cancelled_at_slot_release' => now(),
            ]);
        });
    }

    /**
     * Start Visit — vet clicks "Start Visit" with location verification.
     */
    public function startVisit(Appointment $appointment, ?float $latitude = null, ?float $longitude = null): Appointment
    {
        return $this->transitionStatus($appointment, 'in_progress', function ($appt) use ($latitude, $longitude) {
            $appt->update([
                'status' => 'in_progress',
                'visit_started_at' => now(),
                'vet_start_latitude' => $latitude,
                'vet_start_longitude' => $longitude,
            ]);
        });
    }

    /**
     * End Visit — vet clicks "End Visit" with location verification.
     */
    public function endVisit(Appointment $appointment, ?float $latitude = null, ?float $longitude = null, ?string $notes = null): Appointment
    {
        return $this->transitionStatus($appointment, 'completed', function ($appt) use ($latitude, $longitude, $notes) {
            $updateData = [
                'status' => 'completed',
                'completed_at' => now(),
                'visit_ended_at' => now(),
                'vet_end_latitude' => $latitude,
                'vet_end_longitude' => $longitude,
                'cancelled_at_slot_release' => now(),
            ];
            if ($notes) $updateData['notes'] = $notes;
            $appt->update($updateData);
            // completed_appointments is now incremented in transitionStatus (LOW-04)
        });
    }

    /**
     * Generic status transition with validation, locking, audit, and notification.
     */
    private function transitionStatus(Appointment $appointment, string $targetStatus, callable $mutator): Appointment
    {
        return DB::transaction(function () use ($appointment, $targetStatus, $mutator) {
            $appointment = Appointment::where('id', $appointment->id)->lockForUpdate()->first();
            $previousStatus = $appointment->status;

            $allowed = self::VALID_TRANSITIONS[$previousStatus] ?? [];
            if (!in_array($targetStatus, $allowed, true)) {
                throw new \DomainException(
                    "Cannot transition appointment from '{$previousStatus}' to '{$targetStatus}'."
                );
            }

            $mutator($appointment);

            // Centralized completed_appointments increment (LOW-04)
            if ($targetStatus === 'completed') {
                $appointment->vetProfile?->increment('completed_appointments');
            }

            // Audit log
            $this->auditService->logStatusChange(
                auth()->id(),
                Appointment::class,
                $appointment->id,
                $previousStatus,
                $targetStatus
            );

            Log::info("Appointment {$targetStatus}", [
                'appointment_uuid' => $appointment->uuid,
                'from' => $previousStatus,
                'to' => $targetStatus,
            ]);

            // Notify
            try {
                $notifyUser = (auth()->id() === $appointment->user_id)
                    ? $appointment->vetProfile?->user
                    : $appointment->user;

                $notifyUser?->notify(
                    new AppointmentStatusNotification($appointment, $previousStatus)
                );
            } catch (\Throwable $e) {
                report($e);
            }

            return $appointment->fresh(['user:id,name', 'vetProfile:id,user_id,uuid,clinic_name,vet_name', 'pet:id,name,species']);
        });
    }

    /**
     * Get user appointments with filters.
     */
    public function getUserAppointments(
        User $user,
        ?string $status = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Appointment::forUser($user->id)
            ->with(['vetProfile:id,user_id,uuid,clinic_name,vet_name,phone', 'pet:id,name,species']);

        if ($status) {
            if ($status === 'cancelled') {
                $query->whereIn('status', ['cancelled', 'cancelled_by_user', 'cancelled_by_vet']);
            } else {
                $query->byStatus($status);
            }
        }

        return $query->orderByDesc('scheduled_at')->paginate($perPage);
    }

    /**
     * Get vet appointments with filters.
     */
    public function getVetAppointments(
        int $vetProfileId,
        ?string $status = null,
        ?string $date = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Appointment::forVet($vetProfileId)
            ->with(['user:id,name,email,phone', 'pet:id,name,species']);

        if ($status) {
            if ($status === 'cancelled') {
                $query->whereIn('status', ['cancelled', 'cancelled_by_user', 'cancelled_by_vet']);
            } else {
                $query->byStatus($status);
            }
        }

        if ($date) {
            $query->onDate($date);
        }

        return $query->orderByDesc('scheduled_at')->paginate($perPage);
    }

    /**
     * Find appointment by UUID.
     */
    public function findByUuid(string $uuid): ?Appointment
    {
        return Appointment::where('uuid', $uuid)
            ->with(['user:id,name', 'vetProfile:id,user_id,uuid,clinic_name,vet_name', 'pet:id,name,species'])
            ->first();
    }

    /**
     * Find appointment by UUID, with numeric ID fallback for legacy clients.
     */
    public function findByReference(string $reference): ?Appointment
    {
        $query = Appointment::with(['user:id,name', 'vetProfile:id,user_id,uuid,clinic_name,vet_name', 'pet:id,name,species']);

        if (is_numeric($reference)) {
            return $query->where('id', (int) $reference)
                ->orWhere('uuid', $reference)
                ->first();
        }

        return $query->where('uuid', $reference)->first();
    }

    /**
     * HIGH-04 FIX: Expire stale appointments that are past their scheduled time.
     * - Pending appointments older than 24 hours or past scheduled_at -> cancelled
     * - Accepted/confirmed appointments past scheduled_at + duration -> no_show
     */
    public function expireStale(): int
    {
        $expiredCount = 0;

        // 1. Cancel pending appointments that are past their scheduled time
        $stalePending = Appointment::whereIn('status', ['pending'])
            ->where('scheduled_at', '<', now())
            ->get();

        foreach ($stalePending as $appointment) {
            try {
                $appointment->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => 'Auto-cancelled: appointment time passed without vet response',
                    'cancelled_at' => now(),
                    'cancelled_at_slot_release' => now(),
                ]);
                $this->auditService->logStatusChange(
                    null, Appointment::class, $appointment->id, 'pending', 'cancelled', 'Auto-expired'
                );
                $expiredCount++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // 2. Mark accepted/confirmed appointments as no_show if past scheduled_at + 30 min buffer
        $staleAccepted = Appointment::whereIn('status', ['accepted', 'confirmed'])
            ->whereRaw("DATE_ADD(scheduled_at, INTERVAL (duration_minutes + 30) MINUTE) < NOW()")
            ->get();

        foreach ($staleAccepted as $appointment) {
            try {
                $appointment->update([
                    'status' => 'no_show',
                    'cancelled_at_slot_release' => now(),
                ]);
                $this->auditService->logStatusChange(
                    null, Appointment::class, $appointment->id, $appointment->status, 'no_show', 'Auto-marked no_show'
                );
                $expiredCount++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($expiredCount > 0) {
            Log::info("Expired {$expiredCount} stale appointments");
        }

        return $expiredCount;
    }

    /**
     * Get available slots for a vet on a given date.
     */
    public function getAvailableSlots(VetProfile $vetProfile, string $date): array
    {
        $dayOfWeek = \Carbon\Carbon::parse($date)->dayOfWeek;

        // Get vet's working hours for this day
        $availabilities = $vetProfile->availabilities()
            ->where('day_of_week', $dayOfWeek)
            ->get();

        if ($availabilities->isEmpty() && !$vetProfile->is_24_hours) {
            return [];
        }

        // CRIT-03 FIX: Get existing bookings with duration to block all overlapping slots
        $bookings = Appointment::forVet($vetProfile->id)
            ->onDate($date)
            ->whereIn('status', ['pending', 'accepted', 'confirmed', 'in_progress'])
            ->select('scheduled_at', 'duration_minutes')
            ->get();

        // Build list of all blocked 30-min slots based on booking durations
        $slotDuration = 30; // minutes
        $blockedSlots = [];
        foreach ($bookings as $booking) {
            $startMinutes = (int) $booking->scheduled_at->format('H') * 60
                          + (int) $booking->scheduled_at->format('i');
            $endMinutes = $startMinutes + ($booking->duration_minutes ?? 30);

            // Block all 30-min slots that overlap with this booking
            for ($m = $startMinutes; $m < $endMinutes; $m += $slotDuration) {
                $blockedSlots[] = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
            }
        }
        $blockedSlots = array_unique($blockedSlots);

        $slots = [];

        if ($vetProfile->is_24_hours) {
            $start = 0;
            $end = 24 * 60;
        } else {
            foreach ($availabilities as $availability) {
                $openMinutes = (int) substr($availability->open_time, 0, 2) * 60
                    + (int) substr($availability->open_time, 3, 2);
                $closeMinutes = (int) substr($availability->close_time, 0, 2) * 60
                    + (int) substr($availability->close_time, 3, 2);

                for ($m = $openMinutes; $m + $slotDuration <= $closeMinutes; $m += $slotDuration) {
                    $time = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
                    if (!in_array($time, $blockedSlots)) {
                        $slots[] = $time;
                    }
                }
            }

            return array_values(array_unique($slots));
        }

        // 24-hour case
        for ($m = 0; $m + $slotDuration <= 24 * 60; $m += $slotDuration) {
            $time = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
            if (!in_array($time, $blockedSlots)) {
                $slots[] = $time;
            }
        }

        return $slots;
    }
}
