<?php
namespace Beto\Quizwebapp\Controllers;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use RainLab\User\Models\User;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        \Log::info('JWT_SECRET = ' . env('OPENAI_API_KEY'));

        $token = $request->cookie('authToken');
        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));

            // Tìm user từ DB
            $user = User::find($decoded->sub);
            if (!$user) {
                return response()->json(['error' => 'User not found'], 401);
            }

            // Gắn user vào request (dùng userResolver chuẩn của Laravel)
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
