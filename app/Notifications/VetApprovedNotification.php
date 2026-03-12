<?php

namespace App\Notifications;

use App\Models\VetProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class VetApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private VetProfile $vetProfile
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'vet_approved',
            'vet_profile_uuid' => $this->vetProfile->uuid,
            'message'         => 'Your vet profile has been approved. You can now log in and start accepting appointments.',
        ];
    }
}
