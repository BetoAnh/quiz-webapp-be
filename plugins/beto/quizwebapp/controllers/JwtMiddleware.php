<?php
namespace Beto\Quizwebapp\Controllers;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use RainLab\User\Models\User;

class JwtMiddleware
{
    // TTL cache user (giây)
    protected $cacheTTL = 300; // 5 phút

    public function handle(Request $request, Closure $next)
    {
        // Cho phép preflight CORS
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        $token = $request->cookie('authToken');
        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            // Giải mã token
            $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));

            // Tạo key cache dựa trên token
            $cacheKey = 'jwt_user_' . md5($token);

            // Lấy user từ cache hoặc DB
            $user = Cache::store('redis')->remember($cacheKey, $this->cacheTTL, function () use ($decoded) {
                return User::find($decoded->sub);
            });

            if (!$user) {
                return response()->json(['error' => 'User not found'], 401);
            }

            // Gắn user vào request
            $request->setUserResolver(fn() => $user);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Invalid or expired token',
                'message' => $e->getMessage()
            ], 401);
        }

        return $next($request);
    }
}
