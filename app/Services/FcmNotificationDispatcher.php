<?php

namespace App\Services;

use App\Contracts\NotificationDispatcher;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class FcmNotificationDispatcher implements NotificationDispatcher
{
    private const FCM_ENDPOINT = 'https://fcm.googleapis.com/fcm/send';

    /**
     * Send a push notification to a user via FCM.
     * Gracefully degrades if the user has no FCM token or the server key is not configured.
     */
    public function sendPush(User $user, string $title, string $body, array $data = []): bool
    {
        $serverKey = config('services.fcm.server_key', '');

        if (empty($serverKey)) {
            Log::warning('FCM server key not configured — push notification skipped', [
                'user_id' => $user->id,
            ]);
            return false;
        }

        // Reload fcm_token directly (it is in $hidden so may not be on the model)
        $fcmToken = $user->getRawOriginal('fcm_token')
            ?? User::where('id', $user->id)->value('fcm_token');

        if (empty($fcmToken)) {
            Log::debug('No FCM token for user — push notification skipped', [
                'user_id' => $user->id,
            ]);
            return false;
        }

        $response = Http::withHeaders([
            'Authorization' => 'key=' . $serverKey,
            'Content-Type'  => 'application/json',
        ])->post(self::FCM_ENDPOINT, [
            'to'           => $fcmToken,
            'notification' => [
                'title' => $title,
                'body'  => $body,
                'sound' => 'default',
            ],
            'data' => $data,
        ]);

        if ($response->status() === 400 || $response->status() === 401) {
            // Stale or invalid token — clear it
            Log::warning('FCM token invalid, clearing', [
                'user_id' => $user->id,
                'status'  => $response->status(),
            ]);
            User::where('id', $user->id)->update(['fcm_token' => null]);
            return false;
        }

        if ($response->failed()) {
            Log::error('FCM push failed', [
                'user_id' => $user->id,
                'status'  => $response->status(),
                'body'    => $response->body(),
            ]);
            return false;
        }

        return true;
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
