<?php
namespace Beto\Quizwebapp\Controllers;

use Illuminate\Routing\Controller;
use Beto\Quizwebapp\Models\Level;

class ApiLevelController extends Controller
{
    public function apiLevels()
    {
        $levels = Level::whereNull('parent_id')
            ->with('children')
            ->get(['id', 'name']); // chỉ lấy id, name

        return response()->json($levels);
    }
}
