<?php
namespace Beto\Quizwebapp\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Beto\Quizwebapp\Models\Quiz;
use Beto\Quizwebapp\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuizController extends Controller
{
    public function index()
    {
        $quizzes = Quiz::with('author:id,first_name,last_name', 'category:id,name')->get();
        return response()->json($quizzes);
    }

    public function myquizzes(Request $request)
    {
        $user = $request->user();

        $quizzes = Quiz::with(['author:id,first_name,last_name', 'category:id,name'])
            ->where('author_id', $user->id)
            ->get();

        return response()->json($quizzes);
    }

    /**
     * API: Lấy 1 quiz theo id
     */
    public function show($id)
    {
        $quiz = Quiz::with([
            'questions',
            'author:id,first_name,last_name',
            'category:id,name',
            'level:id,name,parent_id',
            'level.parent:id,name',
        ])->find($id);

        if (!$quiz) {
            return response()->json(['error' => 'Quiz not found'], 404);
        }
        return response()->json($quiz);
    }

    /**
     * API: Tạo quiz + questions
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'nullable|string|in:public,private',
            'category_id' => 'nullable|integer',
            'level_id' => 'nullable|integer',
            'questions' => 'array|min:1',
            'questions.*.text' => 'required|string',
            'questions.*.options' => 'required|array|min:2',
            'questions.*.correctId' => 'nullable|integer',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 401);
        }

        return DB::transaction(function () use ($request, $user) {
            $quiz = new Quiz;
            $quiz->title = $request->title;
            $quiz->slug = $request->slug ?? Str::slug($request->title . '-' . Str::random(5));
            $quiz->description = $request->description ?? '';
            $quiz->visibility = $request->visibility ?? 'public';
            $quiz->category_id = $request->category_id ?? null;
            $quiz->level_id = $request->level_id ?? null;
            $quiz->author_id = $user->id;
            $quiz->save();

            foreach ($request->questions ?? [] as $q) {
                $quiz->questions()->create([
                    'text' => $q['text'],
                    'options' => $q['options'],
                    'correct_id' => $q['correctId'] ?? null,
                ]);
            }

            return response()->json([
                'success' => true,
                'quiz' => $quiz->load('questions')
            ], 201);
        });
    }

    /**
     * API: Cập nhật quiz + questions
     */
    public function update(Request $request, $id)
    {
        $quiz = Quiz::with('questions')->find($id);
        if (!$quiz) {
            return response()->json(['error' => 'Quiz not found'], 404);
        }

        $user = $request->user();
        if (!$user || $quiz->author_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'nullable|string|in:public,private',
            'category_id' => 'nullable|integer',
            'level_id' => 'nullable|integer',
            'questions' => 'array|min:1',
            'questions.*.text' => 'required|string',
            'questions.*.options' => 'required|array|min:2',
            'questions.*.correctId' => 'nullable|integer',
        ]);

        return DB::transaction(function () use ($request, $quiz) {
            // --- Cập nhật quiz ---
            $quiz->title = $request->title;
            $quiz->description = $request->description ?? '';
            $quiz->visibility = $request->visibility ?? 'public';
            $quiz->category_id = $request->category_id ?? null;
            $quiz->level_id = $request->level_id ?? null;
            $quiz->save();

            // --- Đồng bộ câu hỏi ---
            $existingIds = $quiz->questions->pluck('id')->toArray();
            $sentIds = [];

            foreach ($request->questions as $q) {
                // Nếu có id => update
                if (!empty($q['id']) && in_array($q['id'], $existingIds)) {
                    $question = $quiz->questions->firstWhere('id', $q['id']);
                    $question->update([
                        'text' => $q['text'],
                        'options' => $q['options'],
                        'correct_id' => $q['correctId'] ?? null,
                    ]);
                    $sentIds[] = $q['id'];
                } else {
                    // Tạo mới
                    $new = $quiz->questions()->create([
                        'text' => $q['text'],
                        'options' => $q['options'],
                        'correct_id' => $q['correctId'] ?? null,
                    ]);
                    $sentIds[] = $new->id;
                }
            }

            // Xóa các câu hỏi bị loại bỏ
            $toDelete = array_diff($existingIds, $sentIds);
            if (!empty($toDelete)) {
                Question::whereIn('id', $toDelete)->delete();
            }

            return response()->json([
                'success' => true,
                'quiz' => $quiz->load('questions')
            ]);
        });
    }



    /**
     * API: Xóa quiz + questions
     */
    public function destroy($id)
    {
        $quiz = Quiz::find($id);
        if (!$quiz) {
            return response()->json(['error' => 'Quiz not found'], 404);
        }

        // nếu DB đã cascade delete thì bỏ dòng này
        $quiz->questions()->delete();
        $quiz->delete();

        return response()->json(['success' => true]);
    }
}
