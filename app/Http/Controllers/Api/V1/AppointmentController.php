<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Api\V1\Appointment\UpdateAppointmentStatusRequest;
use App\Models\Appointment;
use App\Models\VetProfile;
use App\Services\AppointmentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AppointmentService $appointmentService
    ) {}

    /**
     * List appointments for the authenticated user.
     * GET /api/v1/appointments
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min((int) ($request->per_page ?? 15), 50);

        $appointments = $this->appointmentService->getUserAppointments(
            $user,
            $request->status,
            $perPage
        );

        return $this->success('Appointments retrieved successfully', [
            'appointments' => $appointments->items(),
            'pagination' => [
                'current_page' => $appointments->currentPage(),
                'last_page'    => $appointments->lastPage(),
                'per_page'     => $appointments->perPage(),
                'total'        => $appointments->total(),
            ],
        ]);
    }

    /**
     * List appointments for the authenticated vet.
     * GET /api/v1/appointments/vet
     */
    public function vetIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        $vetProfile = VetProfile::where('user_id', $user->id)->first();

        if (!$vetProfile) {
            return $this->notFound('Vet profile not found.');
        }

        $perPage = min((int) ($request->per_page ?? 15), 50);

        $appointments = $this->appointmentService->getVetAppointments(
            $vetProfile->id,
            $request->status,
            $request->date,
            $perPage
        );

        return $this->success('Vet appointments retrieved successfully', [
            'appointments' => $appointments->items(),
            'pagination' => [
                'current_page' => $appointments->currentPage(),
                'last_page'    => $appointments->lastPage(),
                'per_page'     => $appointments->perPage(),
                'total'        => $appointments->total(),
            ],
        ]);
    }

    /**
     * Book a new appointment.
     * POST /api/v1/appointments
     */
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $user = $request->user();

        $vetProfile = VetProfile::where('uuid', $request->vet_uuid)->first();

        if (!$vetProfile || !$vetProfile->isApproved()) {
            return $this->error('This vet is not available for appointments.', null, 422);
        }

        try {
            $appointment = $this->appointmentService->create($user, $vetProfile, $request->validated());

            return $this->created('Appointment booked successfully', [
                'appointment' => $appointment,
            ]);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), null, 409);
        }
    }

    /**
     * View a single appointment.
     * GET /api/v1/appointments/{uuid}
     */
    public function show(string $uuid): JsonResponse
    {
        $appointment = $this->appointmentService->findByUuid($uuid);

        if (!$appointment) {
            return $this->notFound('Appointment not found');
        }

        $this->authorize('view', $appointment);

        return $this->success('Appointment retrieved successfully', [
            'appointment' => $appointment,
        ]);
    }

    /**
     * Update appointment status (confirm / complete / cancel).
     * PUT /api/v1/appointments/{uuid}/status
     */
    public function updateStatus(UpdateAppointmentStatusRequest $request, string $uuid): JsonResponse
    {
        $appointment = $this->appointmentService->findByUuid($uuid);

        if (!$appointment) {
            return $this->notFound('Appointment not found');
        }

        try {
            $appointment = match ($request->status) {
                'confirmed' => $this->confirmAppointment($request, $appointment),
                'completed' => $this->completeAppointment($request, $appointment),
                'cancelled' => $this->cancelAppointment($request, $appointment),
                'no_show'   => $this->markNoShow($request, $appointment),
            };

            return $this->success('Appointment status updated', [
                'appointment' => $appointment,
            ]);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }

    /**
     * Get available slots for a vet on a given date.
     * GET /api/v1/appointments/slots/{vet_uuid}?date=YYYY-MM-DD
     */
    public function availableSlots(Request $request, string $vetUuid): JsonResponse
    {
        $vetProfile = VetProfile::where('uuid', $vetUuid)->first();

        if (!$vetProfile) {
            return $this->notFound('Vet profile not found');
        }

        $date = $request->query('date', now()->toDateString());

        $slots = $this->appointmentService->getAvailableSlots($vetProfile, $date);

        return $this->success('Available slots retrieved', [
            'vet_uuid' => $vetUuid,
            'date'     => $date,
            'slots'    => $slots,
        ]);
    }

    // ─── Private helpers ─────────────────────────────────────────────

    private function confirmAppointment(Request $request, Appointment $appointment): Appointment
    {
        $this->authorize('confirm', $appointment);
        return $this->appointmentService->confirm($appointment);
    }

    private function completeAppointment(Request $request, Appointment $appointment): Appointment
    {
        $this->authorize('complete', $appointment);
        return $this->appointmentService->complete($appointment, $request->notes);
    }

    private function cancelAppointment(Request $request, Appointment $appointment): Appointment
    {
        $this->authorize('cancel', $appointment);
        return $this->appointmentService->cancel($appointment, $request->user(), $request->reason);
    }

    private function markNoShow(Request $request, Appointment $appointment): Appointment
    {
        $this->authorize('complete', $appointment);
        return $this->appointmentService->markNoShow($appointment);
    }
}
