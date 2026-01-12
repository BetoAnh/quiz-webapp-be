<?php
namespace Beto\Quizwebapp\Controllers;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use RainLab\User\Models\User;

class JwtOptionalMiddleware
{
    protected $cacheTTL = 300;

    public function handle(Request $request, Closure $next)
    {
        $token = $request->cookie('authToken');

        if ($token) {
            try {
                $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));

                $cacheKey = 'jwt_user_' . md5($token);

                $user = Cache::store('redis')->remember(
                    $cacheKey,
                    $this->cacheTTL,
                    fn() => User::find($decoded->sub)
                );

                if ($user) {
                    $request->setUserResolver(fn() => $user);
                }
            } catch (\Exception $e) {
                // ❗ ignore token lỗi
            }
        }

        return $next($request);
    }
}
