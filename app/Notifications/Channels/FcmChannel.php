<?php

namespace App\Notifications\Channels;

use App\Contracts\NotificationDispatcher;
use Illuminate\Notifications\Notification;

/**
 * Laravel notification channel that routes to FcmNotificationDispatcher.
 *
 * Add FcmChannel::class to a notification's via() and implement toFcm() on
 * that notification returning ['title' => ..., 'body' => ..., 'data' => [...]].
 * The channel silently skips any notification that doesn't implement toFcm().
 *
 * Registered automatically by Laravel's service container (constructor DI).
 */
class FcmChannel
{
    public function __construct(private NotificationDispatcher $dispatcher) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toFcm')) {
            return;
        }

        $payload = $notification->toFcm($notifiable);
        if (empty($payload['title'])) {
            return;
        }

        $this->dispatcher->sendPush(
            $notifiable,
            $payload['title'],
            $payload['body'] ?? '',
            $payload['data'] ?? [],
        );
    }
}
