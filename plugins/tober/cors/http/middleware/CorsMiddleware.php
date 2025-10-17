<?php
namespace Tober\Cors\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $defaultOrigin = '*';
        $defaultHeaders = 'Authorization, Content-Type';
        $defaultMethods = 'GET, HEAD, POST, PUT, DELETE, CONNECT, OPTIONS, TRACE, PATCH';

        // đọc config chuẩn
        $originConfig = config('tober.cors.origin', $defaultOrigin);
        $enabled = config('tober.cors.enabled', false);

        // nếu disable thì trả về *
        if (!$enabled) {
            $originConfig = '*';
        }

        // tách nhiều origin
        $allowedOrigins = array_map('trim', explode(',', $originConfig));

        // origin của request (trình duyệt sẽ gửi header này)
        $requestOrigin = $request->headers->get('Origin');

        $origin = $defaultOrigin;

        if ($requestOrigin && in_array($requestOrigin, $allowedOrigins)) {
            $origin = $requestOrigin;
        }

        $headers = [
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Headers' => config('tober.cors.headers', $defaultHeaders),
            'Access-Control-Allow-Methods' => config('tober.cors.methods', $defaultMethods),
            'Access-Control-Allow-Credentials' => 'true',
        ];

        // xử lý preflight request (OPTIONS)
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)->withHeaders($headers);
        }

        // các request khác
        $response = $next($request);
        return $response->withHeaders($headers);
    }
}
