<?php
namespace Beto\Quizwebapp\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Beto\Quizwebapp\Models\QuizFavorite;
use Beto\Quizwebapp\Models\Quiz;

class QuizFavoriteController extends Controller
{
    public function saveQuiz(Request $request, $id)
    {
        $user = $request->user();

        QuizFavorite::firstOrCreate([
            'user_id' => $user->id,
            'quiz_id' => $id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Quiz saved'
        ]);
    }

    public function unsaveQuiz(Request $request, $id)
    {
        $user = $request->user();

        QuizFavorite::where('user_id', $user->id)
            ->where('quiz_id', $id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Quiz removed from saved'
        ]);
    }

    public function savedQuizzes(Request $request)
    {
        $user = $request->user();

        $quizzes = Quiz::query()
            ->select([
                    'id',
                    'title',
                    'description',
                    'visibility',
                    'author_id',
                    'category_id',
                    'created_at',
                ])
            ->with('category:id,name')
            ->whereHas('favorited_by', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->orderByDesc('beto_quizwebapp_quiz_favorites.created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $quizzes
        ]);
    }
}
