<?php

declare(strict_types=1);

use Siberfx\LaravelGemini\Providers\GeminiProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Gemini API Key
    |--------------------------------------------------------------------------
    |
    | Your Google Gemini API key. You can get it from:
    | https://aistudio.google.com/app/apikey
    |
    */

    'api_key' => env('GEMINI_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Base URI
    |--------------------------------------------------------------------------
    |
    | The base URI for the Gemini API.
    |
    */

    'base_uri' => env('GEMINI_BASE_URI', 'https://generativelanguage.googleapis.com'),

    /*
    |--------------------------------------------------------------------------
    | Default Provider
    |--------------------------------------------------------------------------
    |
    | The default provider to use for API requests.
    |
    */

    'default_provider' => env('GEMINI_DEFAULT_PROVIDER', 'gemini'),

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    |
    | Configuration for different providers. Each provider should specify
    | the class and default models and methods for different capabilities.
    |
    */

    'providers' => [
        'gemini' => [
            'class' => GeminiProvider::class,
            'models' => [
                'text' => 'gemini-2.5-flash-lite',
                'image' => 'gemini-2.5-flash-image-preview',
                'video' => 'veo-3.0-fast-generate-001',
                'audio' => 'gemini-2.5-flash-preview-tts',
                'embedding' => 'gemini-embedding-001',
            ],
            'methods' => [
                'text' => 'generateContent',
                'image' => 'generateContent', // generateContent, predict
                'video' => 'predictLongRunning',
                'audio' => 'generateContent',
            ],
            // Fallback voice for single-speaker TTS when ->voiceName() is not called.
            'default_speech_config' => [
                'voiceName' => 'Kore',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout in seconds for API requests.
    |
    */

    'timeout' => (int) env('GEMINI_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retry Policy
    |--------------------------------------------------------------------------
    |
    | Retries apply to connection errors, 429 and 5xx responses. On 429 the
    | Retry-After header takes precedence over the configured delay.
    |
    */

    'retry_policy' => [
        'max_retries' => (int) env('GEMINI_MAX_RETRIES', 3),
        'retry_delay' => (int) env('GEMINI_RETRY_DELAY', 1000), // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Safety Settings
    |--------------------------------------------------------------------------
    |
    | Default safety settings for content generation.
    |
    */

    'safety_settings' => [
        ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable or disable logging of API requests and responses.
    |
    */

    'logging' => (bool) env('GEMINI_LOGGING', false),

    /*
    |--------------------------------------------------------------------------
    | Stream Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for streaming responses.
    |
    */

    'stream' => [
        'chunk_size' => (int) env('GEMINI_STREAM_CHUNK_SIZE', 1024),
        'timeout' => (int) env('GEMINI_STREAM_TIMEOUT', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    |
    | Defaults for the context caching API.
    |
    */

    'caching' => [
        'default_ttl' => env('GEMINI_CACHE_TTL', '3600s'), // e.g. '300s'
        'max_page_size' => 50, // Default page size for listing caches
    ],

    /*
    |--------------------------------------------------------------------------
    | Long-Running Operations
    |--------------------------------------------------------------------------
    |
    | Polling settings for predictLongRunning requests (e.g. Veo video).
    |
    */

    'long_running' => [
        'poll_interval' => (int) env('GEMINI_POLL_INTERVAL', 5), // seconds
        'timeout' => (int) env('GEMINI_POLL_TIMEOUT', 600), // seconds
    ],

];
