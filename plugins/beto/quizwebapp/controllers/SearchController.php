<?php
namespace Beto\QuizWebApp\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Beto\QuizWebApp\Models\Quiz;
use RainLab\User\Models\User;
// use Beto\QuizWebApp\Models\Category;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = trim($request->get('query', ''));
        $suggest = $request->boolean('suggest', false); // ?suggest=1

        if (!$query) {
            return response()->json([
                'quizzes' => [],
                'users' => [],
                'categories' => [],
            ]);
        }

        if ($suggest) {
            // === Autocomplete mode (header search) ===
            $quizzes = Quiz::where('title', 'LIKE', "%{$query}%")
                ->take(10)
                ->get(['id', 'title']);

            $users = User::where('username', 'LIKE', "%{$query}%")
                ->orWhere('first_name', 'LIKE', "%{$query}%")
                ->orWhere('last_name', 'LIKE', "%{$query}%")
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"])
                ->take(10)
                ->get(['id', 'username', 'first_name', 'last_name'])
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'username' => $user->username,
                        'full_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                    ];
                });

            // Tìm trong categories
            // $categories = Category::where('name', 'LIKE', "%{$query}%")
            //     ->take(10)
            //     ->get(['id', 'title']);

            return response()->json([
                'quizzes' => $quizzes,
                'users' => $users,
                // 'categories' => ...
            ]);
        }

        $quizzes = Quiz::where('title', 'LIKE', "%{$query}%")
            ->paginate(20, ['id', 'title']);

        $users = User::where('username', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->orWhere('first_name', 'LIKE', "%{$query}%")
            ->orWhere('last_name', 'LIKE', "%{$query}%")
            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"])
            ->paginate(20, ['id', 'username', 'email', 'first_name', 'last_name', 'avatar_url']);

        // Tìm trong categories
        // $categories = Category::where('name', 'LIKE', "%{$query}%")
        //     ->paginate(20, ['id', 'title']);

        return response()->json([
            'quizzes' => $quizzes,
            'users' => $users,
            // 'categories' => $categories,
        ]);
    }
}
