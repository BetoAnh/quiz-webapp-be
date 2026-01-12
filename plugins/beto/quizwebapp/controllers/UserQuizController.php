<?php
namespace Beto\Quizwebapp\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Beto\Quizwebapp\Models\UserQuizRecent;

class UserQuizController extends Controller
{
    const MAX_RECENT_QUIZZES = 10;

    /**
     * Ghi nhận user bắt đầu học quiz
     * POST /api/user/quizzes/start-learning
     */
    public function startLearning(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $quizId = (int) $request->input('quiz_id');

        UserQuizRecent::updateOrCreate(
            [
                'user_id' => $user->id,
                'quiz_id' => $quizId,
            ],
            [
                'last_learned_at' => now(),
            ]
        );

        // Cleanup giữ tối đa 10
        $idsToKeep = UserQuizRecent::where('user_id', $user->id)
            ->orderByDesc('last_learned_at')
            ->limit(self::MAX_RECENT_QUIZZES)
            ->pluck('id');

        UserQuizRecent::where('user_id', $user->id)
            ->whereNotIn('id', $idsToKeep)
            ->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Lấy danh sách quiz đã học gần đây
     * GET /api/user/quizzes/recent
     */
    public function recent(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $recentQuizzes = UserQuizRecent::query()
            ->where('user_id', $user->id)
            ->orderByDesc('last_learned_at')
            ->limit(self::MAX_RECENT_QUIZZES)
            ->with([
                'quiz:id,title,description,visibility,author_id,created_at,category_id',
                'quiz.category:id,name'
            ])
            ->get()
            ->map(fn($item) => [
                'id' => $item->quiz->id,
                'title' => $item->quiz->title,
                'description' => $item->quiz->description,
                'visibility' => $item->quiz->visibility,
                'author_id' => $item->quiz->author_id,
                'created_at' => $item->quiz->created_at,
                'category' => $item->quiz->category,
                'last_learned_at' => $item->last_learned_at,
            ]);

        return response()->json([
            'data' => $recentQuizzes
        ]);
    }
}
