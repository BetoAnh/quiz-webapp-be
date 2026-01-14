<?php
namespace Beto\Quizwebapp\Controllers;

use Illuminate\Routing\Controller;
use Beto\Quizwebapp\Models\Category;
use Beto\Quizwebapp\Models\Quiz;
use Cache;

class CategoryController extends Controller
{
    // API 1: Danh sách category phân theo parent_id// API 1: Danh sách category phân theo parent_id (có id + name của parent)
    public function categories()
    {
        $tree = Cache::remember('quiz:categories:tree', 86400, function () {

            $categories = Category::all();

            $grouped = $categories->groupBy('parent_id');

            $buildTree = function ($parentId) use (&$buildTree, $grouped) {
                return ($grouped[$parentId] ?? collect())->map(function ($cat) use ($buildTree) {
                    return [
                        'id' => $cat->id,
                        'name' => $cat->name,
                        'slug' => $cat->slug,
                        'parent_id' => $cat->parent_id,
                        'children' => $buildTree($cat->id)
                    ];
                })->values();
            };

            return $buildTree(null);
        });

        return response()->json([
            'success' => true,
            'data' => $tree
        ]);
    }



    // API 2: Chi tiết category + quiz thuộc category đó
    public function categoryDetail($id)
    {
        $cacheKey = "quiz:category:$id:detail";

        return Cache::remember($cacheKey, 600, function () use ($id) {

            $category = Category::select('id', 'name', 'slug')
                ->find($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
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
                ->with([
                        'category:id,name'
                    ])
                ->where('category_id', $id)
                ->where('visibility', 'public')
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'category' => $category,
                    'quizzes' => $quizzes
                ]
            ]);
        });
    }

}
