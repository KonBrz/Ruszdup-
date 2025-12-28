<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    // Obsłuż preflight dla API i Sanctum (CSRF cookie)
    'paths' => ['api/*', 'sanctum/csrf-cookie', '*'],

    'allowed_methods' => ['*'],
    
    // Dopuszczalne pochodzenia frontendu (dodajemy 127.0.0.1 i porty)
    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:5173'),
        'http://127.0.0.1:5173',
        'http://localhost:3000',
    ],

    // Pozwalamy też na wzorzec localhost z dowolnym portem
    'allowed_origins_patterns' => [
        '/^https?:\/\/localhost(:[0-9]+)?$/',
        '/^https?:\/\/127\.0\.0\.1(:[0-9]+)?$/'
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
