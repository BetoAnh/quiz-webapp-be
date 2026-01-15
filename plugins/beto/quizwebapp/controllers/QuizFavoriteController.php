<?php
namespace Beto\Quizwebapp\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Beto\Quizwebapp\Models\QuizFavorite;
use Beto\Quizwebapp\Models\Quiz;
use Log;

class QuizFavoriteController extends Controller
{

    public function checksaved(Request $request, $quizId)
    {
        $user = $request->user();

        $favorite = QuizFavorite::where('user_id', $user->id)
            ->where('quiz_id', $quizId)
            ->first();

        if ($favorite) {
            return response()->json([
                'saved' => true,
            ]);
        }

        return response()->json([
            'saved' => false,
        ]);
    }

    public function toggle(Request $request, $quizId)
    {
        $user = $request->user();

        $favorite = QuizFavorite::where('user_id', $user->id)
            ->where('quiz_id', $quizId)
            ->first();

        if ($favorite) {
            $favorite->delete();

            return response()->json([
                'success' => true,
                'saved' => false,
                'message' => 'Quiz removed from saved'
            ]);
        }

        QuizFavorite::create([
            'user_id' => $user->id,
            'quiz_id' => $quizId,
        ]);

        return response()->json([
            'success' => true,
            'saved' => true,
            'message' => 'Quiz saved'
        ]);
    }

    public function savedQuizzes(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

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
                ->get();

            return response()->json([
                'success' => true,
                'data' => $quizzes
            ]);

        } catch (\Throwable $e) {
            Log::error('savedQuizzes error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error'
            ], 500);
        }
    }

}
