<?php

namespace App\Notifications;

use App\Models\PetReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OverdueMedicationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private PetReminder $reminder
    ) {}

    public function via(object $notifiable): array
    {
        // Medication reminders are always urgent - send via all available channels
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $petName = $this->reminder->pet->name;
        $medicationName = $this->reminder->medication?->medication_name ?? 'medication';
        $overdueTime = $this->reminder->scheduled_at->diffForHumans();

        return (new MailMessage)
            ->subject("🚨 Overdue Medication for {$petName}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your pet {$petName} has an overdue medication dose:")
            ->line("**{$medicationName}**")
            ->line("This dose was scheduled {$overdueTime}")
            ->line($this->reminder->description)
            ->when($this->reminder->medication, function ($mail) {
                $med = $this->reminder->medication;
                return $mail
                    ->line("Dosage: {$med->dosage} {$med->dosage_unit}")
                    ->line("Administration: {$med->administration_method}")
                    ->when($med->food_instructions, function ($m) use ($med) {
                        return $m->line("Food instructions: {$med->food_instructions}");
                    });
            })
            ->action('Log Medication', url("/pets/{$this->reminder->pet->id}/medications"))
            ->line('Please ensure your pet receives their medication as soon as possible.')
            ->line('If you have concerns, contact your veterinarian immediately.');
    }

    public function toArray(object $notifiable): array
    {
        $overdueMinutes = $this->reminder->scheduled_at->diffInMinutes(now());
        $medication = $this->reminder->medication;
        
        return [
            'type' => 'overdue_medication',
            'title' => '🚨 Overdue Medication Alert',
            'pet_id' => $this->reminder->pet_id,
            'pet_name' => $this->reminder->pet->name,
            'reminder_id' => $this->reminder->uuid,
            'medication_id' => $medication?->uuid,
            'medication_name' => $medication?->medication_name,
            'dosage' => $medication ? "{$medication->dosage} {$medication->dosage_unit}" : null,
            'administration_method' => $medication?->administration_method,
            'scheduled_at' => $this->reminder->scheduled_at->toIso8601String(),
            'overdue_minutes' => $overdueMinutes,
            'overdue_text' => $this->reminder->scheduled_at->diffForHumans(),
            'urgency' => $overdueMinutes > 60 ? 'critical' : 'high',
            'instructions' => $this->reminder->description,
            'food_instructions' => $medication?->food_instructions,
            'created_at' => now()->toIso8601String(),
        ];
    }
}