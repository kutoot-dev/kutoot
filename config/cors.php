<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Allow the kutoot-frontend (Next.js) to access the Laravel API.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],


    'allowed_origins' => env('CORS_ALLOWED_ORIGINS')
        ? array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS')))
        : [
            'http://localhost:3000',
            'http://localhost:3001',
            'http://127.0.0.1:3000',
            'https://admin.kutoot.com',
            'https://frontend.kutoot.com',
            'https://kutoot.com',
	    'https://www.kutoot.com',
	    'https://main.d1m3jak1924r3d.amplifyapp.com',
        'https://main.d2gkmd4xrkda1d.amplifyapp.com',
	    'https://sanjeev-test.d1m3jak1924r3d.amplifyapp.com',
	    'https://kutoot-backend.s3.ap-south-1.amazonaws.com'
	   
        ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
