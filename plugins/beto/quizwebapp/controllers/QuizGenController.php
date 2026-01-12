<?php
namespace Beto\Quizwebapp\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Beto\Quizwebapp\Models\QuizTemp;
use Beto\Quizwebapp\Jobs\GenerateQuizJob;
use Beto\Quizwebapp\Models\AiQuota;

class QuizGenController extends Controller
{
    public function generateFromFile(Request $request)
    {
        $user = $request->user();
        $today = now()->toDateString();

        /**
         * 1️⃣ Check quota AI theo ngày
         */
        $quota = AiQuota::firstOrCreate(
            [
                'user_id' => $user->id,
                'date' => $today,
            ],
            [
                'used' => 0,
                'limit' => 2,
            ]
        );

        if ($quota->used >= $quota->limit) {
            return response()->json([
                'error' => '❌ Bạn đã dùng hết lượt tạo quiz AI hôm nay.'
            ], 429);
        }

        /**
         * 2️⃣ Giới hạn tối đa 5 quiz_temp (chỉ tính pending + ready)
         */
        $activeTempCount = QuizTemp::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'ready'])
            ->count();

        if ($activeTempCount >= 5) {
            return response()->json([
                'error' => '❌ Bạn chỉ được lưu tối đa 5 quiz tạm.'
            ], 403);
        }

        /**
         * 3️⃣ Kiểm tra file upload
         */
        if (!$request->hasFile('file')) {
            return response()->json([
                'error' => '❌ Không có file tải lên.'
            ], 400);
        }

        /**
         * 4️⃣ Validate + extract text
         */
        $validation = \Beto\Quizwebapp\Classes\QuizGenerator::validateFile(
            $request->file('file')
        );

        if (!$validation['valid']) {
            return response()->json([
                'error' => $validation['error']
            ], 422);
        }

        $text = $validation['text'];
        $numQuestions = $request->input('numQuestions');
        $numQuestions = is_numeric($numQuestions) && $numQuestions > 0
            ? (int) $numQuestions
            : null;

        /**
         * 5️⃣ Tạo quiz_temp placeholder
         */
        $quizTemp = QuizTemp::create([
            'user_id' => $user->id,
            'data' => null,
            'status' => 'pending',
        ]);

        /**
         * 6️⃣ Dispatch job async qua Redis
         */
        GenerateQuizJob::dispatch(
            $quizTemp->id,
            $text,
            $numQuestions,
            $validation['warning'] ?? null
        )->onQueue('ai');

        /**
         * 7️⃣ Trả kết quả cho FE
         */
        return response()->json([
            'quiz_temp_id' => $quizTemp->id,
            'status' => 'pending',
            'message' => 'Quiz đang được tạo, vui lòng chờ vài giây.',
            'warning' => $validation['warning'] ?? null
        ]);
    }

    /**
     * FE poll quiz_temp
     */
    public function getQuizTemp(Request $request, $id)
    {
        $user = $request->user();

        $quiz = QuizTemp::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$quiz) {
            return response()->json([
                'status' => 'pending'
            ]);
        }

        return response()->json([
            'status' => $quiz->status,
            'quiz' => $quiz->status === 'ready' ? $quiz->data : null,
            'warning' => $quiz->warning,
        ]);
    }
}
