<?php namespace Tober\Cors\Http\Middleware;

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

        $origin = config('tober.cors::origin', $defaultOrigin);

        if (!config('tober.cors::enabled', false)) {
            $origin = '*';
        }

        if (str_contains($origin, ',')) {
            $allowedOrigins = explode(',', $origin);
        }
        else {
            $allowedOrigins = [$origin];
        }

        $origin = collect($allowedOrigins)->first();

        $requestOrigin = request()->headers->get('referer') ?? null;

        if ($requestOrigin) {
            $parsedRequestOrigin = parse_url($requestOrigin);

            if ($parsedRequestOrigin && isset($parsedRequestOrigin['scheme']) && isset($parsedRequestOrigin['host'])) {
                $requestOrigin = $parsedRequestOrigin['scheme'] . '://' . $parsedRequestOrigin['host'];

                $allowedRequestOrigin = collect($allowedOrigins)->filter(function ($allowedOrigin) use ($requestOrigin) {
                    return $requestOrigin === $allowedOrigin;
                })->first();

                if ($allowedRequestOrigin) {
                    if (isset($parsedRequestOrigin['port'])) {
                        $allowedRequestOrigin .= ':' . $parsedRequestOrigin['port'];
                    }

                    $origin = $allowedRequestOrigin;
                }
            }
        }

        $headers = [
            'Access-Control-Allow-Origin'  => $origin,
            'Access-Control-Allow-Headers' => config('tober.cors::headers', $defaultHeaders),
            'Access-Control-Allow-Methods' => config('tober.cors::methods', $defaultMethods)
        ];

        if ($request->isMethod('OPTIONS')) {
            return response(null, 200, $headers);
        }

        $response = $next($request);

        $response->headers->add($headers);

        return $response;
    }
}
