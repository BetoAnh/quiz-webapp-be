# October CMS Cross Origin Resource Sharing

## 1. Check config and configure
```php
<?php

return [
    'enabled' => env('CORS_ENABLED', false),
    'origin' => env('CORS_ORIGIN', '*'),
    'headers' => 'Authorization, Content-Type',
    'methods' => 'GET, HEAD, POST, PUT, DELETE, CONNECT, OPTIONS, TRACE, PATCH'
];
```

## 2. Configure project enviroment
```ini
# CORS
CORS_ENABLED=true
CORS_ORIGIN=https://example.com,http://localhost
```

## 3. Use middleware globally (Kernel.php) or on specific routes
```php
\Tober\Cors\Http\Middleware\CorsMiddleware::class
```
