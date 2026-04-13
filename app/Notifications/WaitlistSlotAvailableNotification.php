<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\AppointmentWaitlist;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WaitlistSlotAvailableNotification extends Notification
{
    use Queueable;

    public function __construct(
        private AppointmentWaitlist $waitlistEntry,
        private Appointment $cancelledAppointment
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'waitlist_slot_available',
            'title' => 'Appointment Slot Available!',
            'message' => "A slot has become available with {$this->cancelledAppointment->vetProfile->clinic_name} on {$this->waitlistEntry->preferred_date->format('M d, Y')}. Book now before it's taken!",
            'waitlist_uuid' => $this->waitlistEntry->uuid,
            'vet_uuid' => $this->cancelledAppointment->vetProfile->uuid,
            'date' => $this->waitlistEntry->preferred_date->toDateString(),
            'time' => $this->cancelledAppointment->scheduled_at->format('H:i'),
        ];
    }
}
