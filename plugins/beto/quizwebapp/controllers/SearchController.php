<?php
namespace Beto\QuizWebApp\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Beto\QuizWebApp\Models\Quiz;
use RainLab\User\Models\User;
use Cache;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = trim($request->get('query', ''));
        $suggest = $request->boolean('suggest', false);
        $page = max((int) $request->get('page', 1), 1);

        // 1️⃣ Không search nếu query quá ngắn
        if (strlen($query) < 2) {
            return response()->json([
                'quizzes' => [],
                'users' => [],
            ]);
        }

        /**
         * ======================================================
         * 2️⃣ AUTOCOMPLETE / SUGGEST MODE (CÓ CACHE)
         * ======================================================
         */
        if ($suggest) {
            $normalizedQuery = strtolower($query);
            $cacheKey = 'search:suggest:' . md5($normalizedQuery);

            return Cache::remember($cacheKey, 60, function () use ($query) {

                $quizzes = Quiz::where('title', 'LIKE', "%{$query}%")
                    ->limit(10)
                    ->get(['id', 'title']);

                $users = User::where(function ($q) use ($query) {
                    $q->where('username', 'LIKE', "%{$query}%")
                        ->orWhere('first_name', 'LIKE', "%{$query}%")
                        ->orWhere('last_name', 'LIKE', "%{$query}%")
                        ->orWhereRaw(
                            "CONCAT(first_name, ' ', last_name) LIKE ?",
                            ["%{$query}%"]
                        );
                })
                    ->limit(10)
                    ->get(['id', 'username', 'first_name', 'last_name'])
                    ->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'username' => $user->username,
                            'full_name' => trim(
                                ($user->first_name ?? '') . ' ' . ($user->last_name ?? '')
                            ),
                        ];
                    });

                return response()->json([
                    'quizzes' => $quizzes,
                    'users' => $users,
                ]);
            });
        }

        /**
         * ======================================================
         * 3️⃣ FULL SEARCH MODE (KHÔNG CACHE)
         * ======================================================
         */

        $quizzes = Quiz::where('title', 'LIKE', "%{$query}%")
            ->paginate(20, ['id', 'title'], 'quiz_page', $page);

        $users = User::where(function ($q) use ($query) {
            $q->where('username', 'LIKE', "%{$query}%")
                ->orWhere('email', 'LIKE', "%{$query}%")
                ->orWhere('first_name', 'LIKE', "%{$query}%")
                ->orWhere('last_name', 'LIKE', "%{$query}%")
                ->orWhereRaw(
                    "CONCAT(first_name, ' ', last_name) LIKE ?",
                    ["%{$query}%"]
                );
        })
            ->paginate(20, [
                'id',
                'username',
                'email',
                'first_name',
                'last_name',
                'avatar_url'
            ], 'user_page', $page);

        return response()->json([
            'quizzes' => $quizzes,
            'users' => $users,
        ]);
    }
}
