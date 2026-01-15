<?php
namespace Beto\Quizwebapp\Controllers;

use Backend\Classes\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Beto\Quizwebapp\Models\Quiz;
use Beto\Quizwebapp\Models\QuizFavorite;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Quiz mới nhất
     */
    public function latest(Request $request)
    {
        $limit = (int) $request->get('limit', 10);

        $quizzes = Cache::tags(['home_latest'])->remember(
            "latest_{$limit}",
            now()->addMinutes(5),
            function () use ($limit) {
                return Quiz::with(['author:id,first_name,last_name', 'category:id,name'])
                    ->where('visibility', 'public')
                    ->latest()
                    ->take($limit)
                    ->get();
            }
        );

        return response()->json([
            'success' => true,
            'data' => $quizzes
        ]);
    }

    /**
     * Quiz nổi bật (nhiều lượt lưu nhất)
     */
    public function featured(Request $request)
    {
        $limit = (int) $request->get('limit', 10);

        $quizzes = Cache::tags(['home_featured'])->remember(
            "featured_{$limit}",
            now()->addMinutes(10),
            function () use ($limit) {
                return Quiz::with(['author:id,first_name,last_name', 'category:id,name'])
                    ->where('visibility', 'public')
                    ->withCount('favorited_by')
                    ->orderByDesc('favorited_by_count')
                    ->take($limit)
                    ->get();
            }
        );

        return response()->json([
            'success' => true,
            'data' => $quizzes
        ]);
    }

}
