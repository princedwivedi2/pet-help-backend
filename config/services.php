<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'razorpay' => [
        'key_id'         => env('RAZORPAY_KEY_ID', ''),
        'key_secret'     => env('RAZORPAY_KEY_SECRET', ''),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET', ''),
    ],

    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY', ''),
    ],

    'firebase' => [
        // Chat realtime fan-out backend.
        //   'realtime'  - Firebase Realtime Database (default; works without ext-grpc)
        //   'firestore' - Cloud Firestore (richer query/security; REQUIRES ext-grpc on the host)
        // Source of truth stays in MySQL regardless — this only controls the
        // realtime push side-effect.
        'chat_backend' => env('FIREBASE_CHAT_BACKEND', 'realtime'),
    ],

    'openai' => [
        'key'   => env('OPENAI_API_KEY', ''),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

    'agora' => [
        'app_id'          => env('AGORA_APP_ID', ''),
        'app_certificate' => env('AGORA_APP_CERTIFICATE', ''),
    ],

    'payments' => [
        // Set PAYMENTS_MOCK=true to skip all Razorpay API calls during development/testing.
        // The mock-confirm endpoint becomes active; the real /payments/verify path still works.
        // NEVER set true in production — the mock-confirm endpoint enforces this.
        'mock' => env('PAYMENTS_MOCK', false),
    ],

];
