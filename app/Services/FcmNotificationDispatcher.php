<?php

namespace App\Services;

use App\Contracts\NotificationDispatcher;
use App\Models\User;
use Kreait\Firebase\Factory as FirebaseFactory;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\RawMessageFromArray;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class FcmNotificationDispatcher implements NotificationDispatcher
{
    private ?object $messaging = null;

    public function __construct(private FirebaseFactory $firebaseFactory)
    {
    }

    /**
     * Lazy-load the messaging service from the Firebase factory.
     */
    private function getMessaging(): object
    {
        if ($this->messaging === null) {
            $this->messaging = $this->firebaseFactory->createMessaging();
        }
        return $this->messaging;
    }

    /**
     * Send a push notification to a user via FCM using HTTP v1 API.
     * Gracefully degrades if the user has no FCM token or Firebase is not configured.
     */
    public function sendPush(User $user, string $title, string $body, array $data = []): bool
    {
        // Reload fcm_token directly (it is in $hidden so may not be on the model)
        $fcmToken = $user->getRawOriginal('fcm_token')
            ?? User::where('id', $user->id)->value('fcm_token');

        if (empty($fcmToken)) {
            Log::debug('No FCM token for user — push notification skipped', [
                'user_id' => $user->id,
            ]);
            return false;
        }

        try {
            $messaging = $this->getMessaging();

            $message = RawMessageFromArray::fromArray([
                'token' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'data' => $data,
                'android' => [
                    'notification' => [
                        'sound' => 'default',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ],
                    ],
                ],
                'webpush' => [
                    'notification' => [
                        'icon' => 'https://example.com/icon.png',
                    ],
                ],
            ]);

            $report = $messaging->send($message);

            if ($report->isSuccess()) {
                return true;
            }

            // Token invalid or expired — clear it
            if ($report->hasFailures()) {
                Log::warning('FCM send had failures, clearing token', [
                    'user_id' => $user->id,
                    'failures' => $report->failures(),
                ]);
                User::where('id', $user->id)->update(['fcm_token' => null]);
            }

            return false;
        } catch (NotFound $e) {
            // Invalid registration token
            Log::warning('FCM token invalid (NotFound), clearing', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            User::where('id', $user->id)->update(['fcm_token' => null]);
            return false;
        } catch (\Throwable $e) {
            Log::error('FCM push failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send an email notification.
     * Delegates to Laravel's mail system via the configured mailer.
     */
    public function sendEmail(User $user, string $subject, string $template, array $data = []): bool
    {
        try {
            Mail::send($template, $data, function ($message) use ($user, $subject) {
                $message->to($user->email, $user->name)->subject($subject);
            });
            return true;
        } catch (\Throwable $e) {
            Log::error('Email notification failed', [
                'user_id' => $user->id,
                'subject' => $subject,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send an SMS notification (stub — wire to Twilio/SNS when credentials are available).
     */
    public function sendSms(string $phoneNumber, string $message): bool
    {
        Log::info('SMS notification stub called', [
            'phone'   => $phoneNumber,
            'message' => $message,
        ]);
        return false;
    }
}
