<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Allow your React frontend
    'allowed_origins' => [
        'http://localhost:3000',
        'http://192.168.0.119:3000',
        'https://fitnessapp-mscitproject-voq7-ktdmtho3u-heet1206s-projects.vercel.app',
        'https://aifitnessapp-semv.onrender.com'
    ],

    // For development you can allow all (optional)
    // 'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];