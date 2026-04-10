<?php

namespace App\Notifications;

use App\Models\PetDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentExpiryNotification extends Notification
{
    use Queueable;

    public function __construct(
        private PetDocument $document
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $petName = $this->document->pet->name;
        $documentType = str_replace('_', ' ', $this->document->document_type);
        $expiryDate = $this->document->expiry_date;
        $daysUntilExpiry = now()->diffInDays($expiryDate);

        $urgencyText = match(true) {
            $daysUntilExpiry <= 7 => '🚨 URGENT',
            $daysUntilExpiry <= 14 => '⚠️ Important',
            default => '📋 Reminder'
        };

        return (new MailMessage)
            ->subject("{$urgencyText}: {$petName}'s " . ucwords($documentType) . " Expires Soon")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your pet {$petName}'s document is expiring soon:")
            ->line("**" . ucwords($documentType) . "**: {$this->document->title}")
            ->line("Expires on: {$expiryDate->format('M j, Y')} ({$daysUntilExpiry} days)")
            ->when($this->document->description, function ($mail) {
                return $mail->line($this->document->description);
            })
            ->when($this->document->vetProfile, function ($mail) {
                return $mail->line("Issued by: {$this->document->vetProfile->vet_name}");
            })
            ->action('View Document', url("/pets/{$this->document->pet->id}/documents"))
            ->line($this->getActionAdvice($this->document->document_type))
            ->line('Keep your pet\'s documents up to date! 🐾');
    }

    public function toArray(object $notifiable): array
    {
        $daysUntilExpiry = now()->diffInDays($this->document->expiry_date);
        $urgency = match(true) {
            $daysUntilExpiry <= 3 => 'critical',
            $daysUntilExpiry <= 7 => 'high', 
            $daysUntilExpiry <= 14 => 'medium',
            default => 'low'
        };

        return [
            'type' => 'document_expiry',
            'title' => '📋 Pet Document Expiring Soon',
            'pet_id' => $this->document->pet_id,
            'pet_name' => $this->document->pet->name,
            'document_id' => $this->document->uuid,
            'document_title' => $this->document->title,
            'document_type' => $this->document->document_type,
            'document_type_display' => ucwords(str_replace('_', ' ', $this->document->document_type)),
            'expiry_date' => $this->document->expiry_date->toIso8601String(),
            'days_until_expiry' => $daysUntilExpiry,
            'urgency' => $urgency,
            'vet_name' => $this->document->vetProfile?->vet_name,
            'action_advice' => $this->getActionAdvice($this->document->document_type),
            'created_at' => now()->toIso8601String(),
        ];
    }

    private function getActionAdvice(string $documentType): string
    {
        return match($documentType) {
            'vaccination_record' => 'Schedule a vaccination appointment with your vet to keep your pet protected.',
            'insurance_policy' => 'Contact your insurance provider to renew your pet\'s coverage.',
            'health_certificate' => 'Visit your vet to get an updated health certificate if needed for travel.',
            'microchip_info' => 'Check with your vet or microchip provider about updating registration.',
            'registration' => 'Renew your pet\'s registration with local authorities.',
            default => 'Contact your veterinarian or relevant provider to renew this document.'
        };
    }
}