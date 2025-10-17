<?php
namespace Beto\Quizwebapp\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Carbon\Carbon;
use RainLab\User\Models\User;

class AuthController extends Controller
{
    private $jwtSecret;
    private $tokenTTL; // số phút

    public function __construct()
    {
        $this->jwtSecret = env('JWT_SECRET', 'fallback_key');
        $this->tokenTTL = env('JWT_TTL', 60 * 24 * 7); // mặc định 7 ngày
    }

    /** -------- LOGIN -------- */
    public function login(Request $request)
    {
        // Validate input
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        if (!\Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401); // Unauthorized
        }

        $token = $this->generateToken($user->id);

        return $this->withAuthCookie(
            response()->json([
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'last_name' => $user->last_name,
                ]
            ], 200), // 200 OK
            $token
        );
    }


    /** -------- REGISTER -------- */
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();

            if (isset($errors['email']) && in_array("The email has already been taken.", $errors['email'])) {
                return response()->json([
                    'message' => 'Email has already been registered',
                ], 409); // 409 Conflict
            }

            return response()->json([
                'message' => 'Validation failed',
                'errors' => $errors,
            ], 422);
        }

        $user = new User();
        $user->first_name = $request->first_name;
        $user->email = $request->email;
        $user->password = $request->password;
        $user->save();

        $token = $this->generateToken($user->id);

        return $this->withAuthCookie(
            response()->json([
                'message' => 'User registered successfully',
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'username' => $user->username,
                    'email' => $user->email,
                ]
            ], 201), // 201 Created
            $token
        );
    }


    /** -------- LOGOUT -------- */
    public function logout()
    {
        return response()->json(['message' => 'Logged out'])
            ->cookie('authToken', '', -1, '/', null, $this->isSecure(), true, false, 'Strict');
    }

    /** -------- PROFILE -------- */
    public function auth(Request $request)
    {
        $user = $request->user(); // lấy từ middleware

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json([
            'id' => $user->id,
            'first_name' => $user->first_name,
            'email' => $user->email,
            'username' => $user->username,
            'last_name' => $user->last_name,
        ]);
    }


    /** -------- HELPER -------- */
    private function generateToken($userId)
    {
        $payload = [
            'sub' => $userId,
            'iat' => Carbon::now()->timestamp,
            'exp' => Carbon::now()->addMinutes($this->tokenTTL)->timestamp
        ];
        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    private function withAuthCookie($response, $token)
    {
        return $response->cookie(
            'authToken',
            $token,
            $this->tokenTTL, // phút
            '/',
            null,
            $this->isSecure(),
            true,   // HttpOnly
            false,
            'Strict'
        );
    }

    private function isSecure()
    {
        return !app()->environment('local'); // local = false, production = true
    }
}
