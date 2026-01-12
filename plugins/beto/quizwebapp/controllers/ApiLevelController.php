<?php
namespace Beto\Quizwebapp\Controllers;

use Illuminate\Routing\Controller;
use Beto\Quizwebapp\Models\Level;
use Cache;

class ApiLevelController extends Controller
{
    public function apiLevels()
    {
        $levels = Cache::remember('quiz:levels:tree', 86400, function () {
            return Level::whereNull('parent_id')
                ->with('children:id,name,parent_id')
                ->get(['id', 'name']);
        });

        return response()->json($levels);
    }
}
