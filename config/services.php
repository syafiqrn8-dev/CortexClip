<?php

use App\Services\AI\Providers\GeminiScriptProvider;
use App\Services\AI\Providers\MockScriptProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'ai' => [
        /*
         * Active provider key. Must exist in the `providers` map below.
         * Switch providers by changing AI_PROVIDER (and supplying that
         * provider's own credentials).
         */
        'provider' => env('AI_PROVIDER', 'mock'),

        'timeout' => env('AI_TIMEOUT', 30),

        /*
        | Provider registry: name => implementation class.
        | Add new providers here; each must implement
        | App\Services\AI\Contracts\ScriptProvider.
        */
        'providers' => [
            'mock' => MockScriptProvider::class,
            'gemini' => GeminiScriptProvider::class,
        ],

        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        ],
    ],

];
