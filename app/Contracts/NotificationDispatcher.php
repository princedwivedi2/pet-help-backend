<?php

namespace App\Contracts;

use App\Models\User;

/**
 * Notification dispatcher interface.
 *
 * Stub for future implementation with FCM (push),
 * email, SMS, or in-app notification channels.
 */
interface NotificationDispatcher
{
    /**
     * Send a push notification to a user.
     *
     * @param User   $user
     * @param string $title
     * @param string $body
     * @param array  $data  Custom payload
     * @return bool
     */
    public function sendPush(User $user, string $title, string $body, array $data = []): bool;

    /**
     * Send an email notification.
     *
     * @param User   $user
     * @param string $subject
     * @param string $template  Mail template name
     * @param array  $data
     * @return bool
     */
    public function sendEmail(User $user, string $subject, string $template, array $data = []): bool;

    /**
     * Send an SMS notification.
     *
     * @param string $phoneNumber
     * @param string $message
     * @return bool
     */
    public function sendSms(string $phoneNumber, string $message): bool;
}
