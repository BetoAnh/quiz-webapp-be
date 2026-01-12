<?php
namespace Beto\Quizwebapp\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Beto\Quizwebapp\Models\QuizTemp;

class QuizTempController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $quizTemps = QuizTemp::where('user_id', $user->id)
            ->where('status', 'ready')
            ->orderByDesc('updated_at')
            ->get(['id', 'data', 'updated_at'])
            ->map(function ($quiz) {
                return [
                    'id' => $quiz->id,
                    'title' => $quiz->data['title'] ?? 'Untitled quiz',
                    'updated_at' => $quiz->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $quizTemps
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        /**
         * 1️⃣ Check giới hạn tối đa 5 quiz_temp
         * (pending + ready)
         */
        $activeTempCount = QuizTemp::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'ready'])
            ->count();

        if ($activeTempCount >= 5) {
            return response()->json([
                'success' => false,
                'message' => '❌ Bạn chỉ được lưu tối đa 5 quiz nháp.'
            ], 403);
        }

        /**
         * 2️⃣ Validate dữ liệu quiz
         * FE gửi toàn bộ form vào field `data`
         */
        $data = $request->input('data');

        if (!$data || !is_array($data)) {
            return response()->json([
                'success' => false,
                'message' => '❌ Dữ liệu quiz không hợp lệ.'
            ], 422);
        }

        /**
         * 3️⃣ Tạo quiz_temp
         * Manual draft => ready luôn
         */
        $quizTemp = QuizTemp::create([
            'user_id' => $user->id,
            'data' => $data,
            'status' => 'ready',
            'warning' => null,
        ]);

        /**
         * 4️⃣ Trả dữ liệu cho FE
         */
        return response()->json([
            'success' => true,
            'message' => 'Đã lưu quiz nháp.',
            'data' => [
                'id' => $quizTemp->id,
                'title' => $data['title'] ?? 'Untitled quiz',
                'updated_at' => $quizTemp->updated_at,
            ]
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        $quizTemp = QuizTemp::where('id', $id)
            ->where('user_id', $user->id)
            ->where('status', 'ready')
            ->first();

        if (!$quizTemp) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz temp not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $quizTemp->id,
                'status' => $quizTemp->status,
                'data' => $quizTemp->data,
                'warning' => $quizTemp->warning,
                'updated_at' => $quizTemp->updated_at,
            ]
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $quizTemp = QuizTemp::where('id', $id)
            ->where('user_id', $user->id)
            ->where('status', 'ready') // chỉ cho xóa quiz usable
            ->first();

        if (!$quizTemp) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz temp not found or not deletable'
            ], 404);
        }

        $quizTemp->delete();

        return response()->json([
            'success' => true,
            'message' => 'Quiz temp deleted successfully'
        ]);
    }
}
