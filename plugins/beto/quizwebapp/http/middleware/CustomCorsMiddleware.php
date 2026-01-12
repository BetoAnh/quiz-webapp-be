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
        logger('CORS HIT', [
            'method' => $request->method(),
            'path' => $request->path(),
            'origin' => $request->headers->get('Origin'),
        ]);

        $defaultOrigin = '*';
        $defaultHeaders = 'Authorization, Content-Type';
        $defaultMethods = 'GET, HEAD, POST, PUT, DELETE, CONNECT, OPTIONS, TRACE, PATCH';

        $originConfig = config('beto.quizwebapp.origin', $defaultOrigin);
        $enabled = config('beto.quizwebapp.enabled', false);

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
            'Access-Control-Allow-Headers' => config('beto.quizwebapp.headers', $defaultHeaders),
            'Access-Control-Allow-Methods' => config('beto.quizwebapp.methods', $defaultMethods),
            'Access-Control-Allow-Credentials' => 'true',
        ];

        if ($request->isMethod('OPTIONS')) {
            return response('', 200)->withHeaders($headers);
        }

        $response = $next($request);
        return $response->withHeaders($headers);

    }
}
