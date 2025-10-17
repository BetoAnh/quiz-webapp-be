<?php

return [
    'enabled' => env('CORS_ENABLED', false),
    'origin' => env('CORS_ORIGIN', '*'),
    'headers' => 'Authorization, Content-Type',
    'methods' => 'GET, HEAD, POST, PUT, DELETE, CONNECT, OPTIONS, TRACE, PATCH'
];
