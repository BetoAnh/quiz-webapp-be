<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Beto\Quizwebapp\Controllers\AuthController;
use Beto\Quizwebapp\Controllers\JwtMiddleware;
use Beto\Quizwebapp\Controllers\QuizController;
use Beto\Quizwebapp\Controllers\UserController;
use Beto\Quizwebapp\Controllers\SearchController;
use Beto\Quizwebapp\Controllers\CategoryController;
use Beto\Quizwebapp\Controllers\ApiLevelController;
use Beto\Quizwebapp\Controllers\HomeController;
use Beto\Quizwebapp\Controllers\QuizGenController;
use Beto\Quizwebapp\Controllers\QuizTempController;
use Beto\Quizwebapp\Controllers\UserQuizController;
use Beto\Quizwebapp\Controllers\JwtOptionalMiddleware;

Route::group([
    'prefix' => 'api',
], function () {

    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/quizzes', [QuizController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'getById']);
    Route::get('/users/username/{username}', [UserController::class, 'getByUsername']);
    Route::get('/search', [SearchController::class, 'search']);
    Route::get('/home/latest', [HomeController::class, 'latest']);
    Route::get('/home/featured', [HomeController::class, 'featured']);
    Route::get('/categories/{id}', [CategoryController::class, 'categoryDetail']);
    Route::get('/categories', [CategoryController::class, 'categories']);
    Route::get('/levels', [ApiLevelController::class, 'apiLevels']);
    Route::middleware([JwtOptionalMiddleware::class])->group(function () {
        Route::get('/quizzes/{id}', [QuizController::class, 'show']);
    });
    Route::middleware([JwtMiddleware::class])->group(function () {
        Route::get('/auth', [AuthController::class, 'auth']);
        Route::post('/quizzes', [QuizController::class, 'store']);       // Tạo quiz mới
        Route::put('/quizzes/{id}', [QuizController::class, 'update']);
        Route::patch('/quizzes/{id}', [QuizController::class, 'update']); // optional
        Route::delete('/quizzes/{id}', [QuizController::class, 'destroy']); // Xóa quiz
        Route::put('/users/profile', [UserController::class, 'updateProfile']);
        Route::put('/users/change-password', [UserController::class, 'changePassword']);
        Route::get('/myquizzes', [QuizController::class, 'myquizzes']);
        Route::post('/generate-quiz', [QuizGenController::class, 'generateFromFile']);
        Route::get('/quiz/temp/{id}', [QuizGenController::class, 'getQuizTemp']);
        Route::get('/quiz-temps', [QuizTempController::class, 'index']);
        Route::get('/quiz-temps/{id}', [QuizTempController::class, 'show']);
        Route::post('/quiz-temps', [QuizTempController::class, 'store']);
        Route::delete('/quiz-temps/{id}', [QuizTempController::class, 'destroy']);
        Route::post('/user/quizzes/start-learning', [UserQuizController::class, 'startLearning']);
        Route::get('/user/quizzes/recent', [UserQuizController::class, 'recent']);
    });
});
