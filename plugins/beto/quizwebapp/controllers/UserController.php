<?php
namespace Beto\Quizwebapp\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use RainLab\User\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public function getById(Request $request, $id)
    {
        $authUser = $request->user(); // user đang đăng nhập

        $user = User::select('id', 'first_name', 'last_name', 'email', 'username', 'notes', 'created_at')
            ->find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Nếu đúng là profile của user đang đăng nhập → lấy toàn bộ quiz
        // ngược lại chỉ lấy quiz public
        $quizzesQuery = $user->quizzes()->with('category:id,name');
        if (!$authUser || $authUser->id !== $user->id) {
            $quizzesQuery->where('visibility', 'public');
        }

        $user->setRelation('quizzes', $quizzesQuery->get());

        return response()->json($user);
    }

    public function getByUsername(Request $request, $username)
    {
        $authUser = $request->user(); // user đang đăng nhập

        $user = User::select('id', 'first_name', 'last_name', 'email', 'username', 'notes', 'created_at')
            ->where('username', $username)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $quizzesQuery = $user->quizzes()->with('category:id,name');
        if (!$authUser || $authUser->id !== $user->id) {
            $quizzesQuery->where('visibility', 'public');
        }

        $user->setRelation('quizzes', $quizzesQuery->get());

        return response()->json($user);
    }



    public function updateProfile(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $rules = [];
        if ($request->has('username')) {
            $rules['username'] = 'string|max:255|unique:users,username,' . $user->id;
        }
        if ($request->has('first_name')) {
            $rules['first_name'] = 'string|max:255';
        }
        if ($request->has('last_name')) {
            $rules['last_name'] = 'string|max:255';
        }
        if ($request->has('notes')) {
            $rules['notes'] = 'string';
        }

        $validated = $request->validate($rules);

        try {
            // Chỉ fill những field được gửi lên
            foreach ($validated as $field => $value) {
                $user->{$field} = $value;
            }
            $user->save();

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => $user->only(['id', 'first_name', 'last_name', 'email', 'username', 'notes', 'created_at']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'oldPassword' => 'required|string',
            'newPassword' => 'required|string|min:8',
            'confirmPassword' => 'required|string|same:newPassword',
        ]);

        if (!Hash::check($validated['oldPassword'], $user->password)) {
            return response()->json(['message' => 'Mật khẩu hiện tại không đúng'], 400);
        }

        try {
            $user->password = $validated['newPassword'];
            $user->save();

            return response()->json(['message' => 'Đổi mật khẩu thành công']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Đổi mật khẩu thất bại',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
