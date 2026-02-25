<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use App\Models\VetProfile;
use App\Notifications\AppointmentBookedNotification;
use App\Notifications\AppointmentStatusNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentService
{
    /**
     * Create a new appointment (with double-booking prevention).
     */
    public function create(User $user, VetProfile $vetProfile, array $data): Appointment
    {
        return DB::transaction(function () use ($user, $vetProfile, $data) {
            // Lock the vet's appointments for the time slot to prevent race conditions
            $conflict = Appointment::where('vet_profile_id', $vetProfile->id)
                ->where('scheduled_at', $data['scheduled_at'])
                ->whereIn('status', ['pending', 'confirmed'])
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                throw new \DomainException('This time slot is already booked.');
            }

            $appointment = Appointment::create([
                'user_id'          => $user->id,
                'vet_profile_id'   => $vetProfile->id,
                'pet_id'           => $data['pet_id'] ?? null,
                'scheduled_at'     => $data['scheduled_at'],
                'duration_minutes' => $data['duration_minutes'] ?? 30,
                'reason'           => $data['reason'],
                'notes'            => $data['notes'] ?? null,
                'status'           => 'pending',
            ]);

            Log::info('Appointment created', [
                'appointment_uuid' => $appointment->uuid,
                'user_id'          => $user->id,
                'vet_profile_id'   => $vetProfile->id,
                'scheduled_at'     => $data['scheduled_at'],
            ]);

            // Notify the vet user
            try {
                $vetProfile->user?->notify(new AppointmentBookedNotification($appointment));
            } catch (\Throwable $e) {
                report($e);
            }

            return $appointment->load(['user:id,name', 'vetProfile:id,uuid,clinic_name,vet_name', 'pet:id,name,species']);
        });
    }

    /**
     * Confirm an appointment (vet action).
     */
    public function confirm(Appointment $appointment): Appointment
    {
        return DB::transaction(function () use ($appointment) {
            if (!$appointment->canBeConfirmed()) {
                throw new \DomainException('This appointment cannot be confirmed.');
            }

            $appointment->update(['status' => 'confirmed']);

            Log::info('Appointment confirmed', [
                'appointment_uuid' => $appointment->uuid,
                'vet_profile_id'   => $appointment->vet_profile_id,
            ]);

            // Notify the pet owner
            try {
                $appointment->user->notify(
                    new AppointmentStatusNotification($appointment, 'pending')
                );
            } catch (\Throwable $e) {
                report($e);
            }

            return $appointment->fresh(['user:id,name', 'vetProfile:id,uuid,clinic_name,vet_name', 'pet:id,name,species']);
        });
    }

    /**
     * Complete an appointment.
     */
    public function complete(Appointment $appointment, ?string $notes = null): Appointment
    {
        if (!$appointment->canBeCompleted()) {
            throw new \DomainException('This appointment cannot be marked as completed.');
        }

        return DB::transaction(function () use ($appointment, $notes) {
            $updateData = ['status' => 'completed'];
            if ($notes) {
                $updateData['notes'] = $notes;
            }

            $appointment->update($updateData);

            Log::info('Appointment completed', [
                'appointment_uuid' => $appointment->uuid,
            ]);

            try {
                $appointment->user->notify(
                    new AppointmentStatusNotification($appointment, 'confirmed')
                );
            } catch (\Throwable $e) {
                report($e);
            }

            return $appointment->fresh(['user:id,name', 'vetProfile:id,uuid,clinic_name,vet_name', 'pet:id,name,species']);
        });
    }

    /**
     * Cancel an appointment.
     */
    public function cancel(Appointment $appointment, User $cancelledBy, string $reason): Appointment
    {
        if (!$appointment->canBeCancelledBy($cancelledBy)) {
            throw new \DomainException('You cannot cancel this appointment.');
        }

        return DB::transaction(function () use ($appointment, $cancelledBy, $reason) {
            $previousStatus = $appointment->status;

            $appointment->update([
                'status'              => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_by'        => $cancelledBy->id,
            ]);

            Log::info('Appointment cancelled', [
                'appointment_uuid' => $appointment->uuid,
                'cancelled_by'     => $cancelledBy->id,
                'reason'           => $reason,
            ]);

            // Notify the other party
            try {
                $notifyUser = ($cancelledBy->id === $appointment->user_id)
                    ? $appointment->vetProfile->user
                    : $appointment->user;

                $notifyUser?->notify(
                    new AppointmentStatusNotification($appointment, $previousStatus)
                );
            } catch (\Throwable $e) {
                report($e);
            }

            return $appointment->fresh(['user:id,name', 'vetProfile:id,uuid,clinic_name,vet_name', 'pet:id,name,species']);
        });
    }

    /**
     * Mark an appointment as no-show (vet action).
     */
    public function markNoShow(Appointment $appointment): Appointment
    {
        if ($appointment->status !== 'confirmed') {
            throw new \DomainException('Only confirmed appointments can be marked as no-show.');
        }

        return DB::transaction(function () use ($appointment) {
            $appointment->update(['status' => 'no_show']);

            Log::info('Appointment marked no-show', [
                'appointment_uuid' => $appointment->uuid,
                'vet_profile_id'   => $appointment->vet_profile_id,
            ]);

            // Notify the pet owner
            try {
                $appointment->user->notify(
                    new AppointmentStatusNotification($appointment, 'confirmed')
                );
            } catch (\Throwable $e) {
                report($e);
            }

            return $appointment->fresh(['user:id,name', 'vetProfile:id,uuid,clinic_name,vet_name', 'pet:id,name,species']);
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
            ->with(['vetProfile:id,uuid,clinic_name,vet_name,phone', 'pet:id,name,species']);

        if ($status) {
            $query->byStatus($status);
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
            ->with(['user:id,name,phone', 'pet:id,name,species']);

        if ($status) {
            $query->byStatus($status);
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
            ->with(['user:id,name', 'vetProfile:id,uuid,clinic_name,vet_name', 'pet:id,name,species'])
            ->first();
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

        // Get existing bookings for this date
        $bookedSlots = Appointment::forVet($vetProfile->id)
            ->onDate($date)
            ->whereIn('status', ['pending', 'confirmed'])
            ->pluck('scheduled_at')
            ->map(fn ($dt) => $dt->format('H:i'))
            ->toArray();

        $slots = [];
        $slotDuration = 30; // minutes

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
                    if (!in_array($time, $bookedSlots)) {
                        $slots[] = $time;
                    }
                }
            }

            return array_values(array_unique($slots));
        }

        // 24-hour case
        for ($m = 0; $m + $slotDuration <= 24 * 60; $m += $slotDuration) {
            $time = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
            if (!in_array($time, $bookedSlots)) {
                $slots[] = $time;
            }
        }

        return $slots;
    }
}
