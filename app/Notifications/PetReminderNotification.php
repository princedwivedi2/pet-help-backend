<?php

namespace App\Notifications;

use App\Models\PetReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PetReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private PetReminder $reminder
    ) {}

    public function via(object $notifiable): array
    {
        $methods = $this->reminder->notification_methods ?? ['database'];
        
        // For now, support database and email
        // TODO: Add FCM/push notifications
        return array_intersect($methods, ['database', 'mail']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $petName = $this->reminder->pet->name;
        $title = $this->reminder->title;
        $scheduledAt = $this->reminder->scheduled_at;

        return (new MailMessage)
            ->subject("Pet Reminder: {$title}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("This is a reminder for your pet {$petName}:")
            ->line("**{$title}**")
            ->line("Scheduled for: {$scheduledAt->format('M j, Y \\a\\t g:i A')}")
            ->when($this->reminder->description, function ($mail) {
                return $mail->line($this->reminder->description);
            })
            ->when($this->reminder->location, function ($mail) {
                return $mail->line("Location: {$this->reminder->location}");
            })
            ->when($this->reminder->cost_estimate, function ($mail) {
                return $mail->line("Estimated cost: $" . number_format($this->reminder->cost_estimate, 2));
            })
            ->action('View Pet Dashboard', url("/pets/{$this->reminder->pet->id}/dashboard"))
            ->line('Take care of your furry friend! 🐾');
    }

    public function toArray(object $notifiable): array
    {
        $priority = $this->reminder->priority ?? 5;
        $isUrgent = $priority >= 8;
        
        return [
            'type' => 'pet_reminder',
            'title' => $isUrgent ? '⚡ Urgent Pet Reminder' : '🔔 Pet Reminder',
            'pet_id' => $this->reminder->pet_id,
            'pet_name' => $this->reminder->pet->name,
            'reminder_id' => $this->reminder->uuid,
            'reminder_title' => $this->reminder->title,
            'reminder_type' => $this->reminder->reminder_type,
            'description' => $this->reminder->description,
            'scheduled_at' => $this->reminder->scheduled_at->toIso8601String(),
            'priority' => $priority,
            'is_urgent' => $isUrgent,
            'location' => $this->reminder->location,
            'cost_estimate' => $this->reminder->cost_estimate,
            'created_at' => now()->toIso8601String(),
        ];
    }
}