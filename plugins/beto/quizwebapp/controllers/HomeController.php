<?php
namespace Beto\Quizwebapp\Controllers;

use Backend\Classes\Controller;
use Illuminate\Http\Request;
use Beto\Quizwebapp\Models\Quiz;

class HomeController extends Controller
{
    /**
     * Lấy danh sách quiz mới nhất
     */
    public function latest(Request $request)
    {
        $limit = $request->get('limit', 10);

        $quizzes = Quiz::with(['author:id,first_name,last_name', 'category:id,name'])
            ->where('visibility', 'public')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $quizzes
        ]);
    }

    /**
     * Lấy danh sách quiz nổi bật (giả lập)
     * Ở đây ta có thể giả lập logic "nổi bật" dựa trên các yếu tố:
     * - số lượng người học cao
     * - được gắn cờ nổi bật (featured)
     * - hoặc đơn giản là random chọn từ quiz công khai
     */
    public function featured(Request $request)
    {
        $limit = $request->get('limit', 10);

        $quizzes = Quiz::with(['author:id,first_name,last_name', 'category:id,name'])
            ->where('visibility', 'public')
            ->inRandomOrder() // giả lập quiz nổi bật
            ->take($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $quizzes
        ]);
    }
}
