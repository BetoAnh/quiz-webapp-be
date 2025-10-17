<?php
namespace Beto\Quizwebapp\Controllers;

use Illuminate\Routing\Controller;
use Beto\Quizwebapp\Models\Category;
use Beto\Quizwebapp\Models\Quiz;

class CategoryController extends Controller
{
    // API 1: Danh sách category phân theo parent_id// API 1: Danh sách category phân theo parent_id (có id + name của parent)
    public function categories()
    {
        $categories = Category::all();

        // Nhóm theo parent_id
        $grouped = $categories->groupBy('parent_id');

        // Hàm đệ quy để build cây
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

        // Bắt đầu từ root (parent_id = null)
        $tree = $buildTree(null);

        return response()->json([
            'success' => true,
            'data' => $tree
        ]);
    }



    // API 2: Chi tiết category + quiz thuộc category đó
    public function categoryDetail($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        // Lấy quiz public kèm theo thông tin category
        $quizzes = Quiz::with('category:id,name')
            ->where('category_id', $id)
            ->where('visibility', 'public')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'quizzes' => $quizzes
            ]
        ]);
    }
}
