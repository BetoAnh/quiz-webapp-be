<?php
namespace Beto\Quizwebapp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CustomCorsMiddleware
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

        $originConfig = config('tober.cors.origin', $defaultOrigin);
        $enabled = config('tober.cors.enabled', false);

        if (!$enabled) {
            $originConfig = '*';
        }

        $allowedOrigins = array_map('trim', explode(',', $originConfig));
        $requestOrigin = $request->headers->get('Origin');
        $origin = $defaultOrigin;

        if ($requestOrigin && in_array($requestOrigin, $allowedOrigins)) {
            $origin = $requestOrigin;
        }

        $headers = [
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Headers' => config('tober.cors.headers', $defaultHeaders),
            'Access-Control-Allow-Methods' => config('tober.cors.methods', $defaultMethods),
            'Access-Control-Allow-Credentials' => 'true', // bạn muốn thêm dòng này
        ];

        if ($request->isMethod('OPTIONS')) {
            return response('', 200)->withHeaders($headers);
        }

        $response = $next($request);
        return $response->withHeaders($headers);
    }
}
