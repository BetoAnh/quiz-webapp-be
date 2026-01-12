<?php
namespace Beto\Quizwebapp;

use Illuminate\Foundation\Http\Kernel as HttpKernel;
use System\Classes\PluginBase;
use RainLab\User\Models\User;
use Beto\Quizwebapp\Models\Quiz;
use Beto\Quizwebapp\Http\Middleware\CustomCorsMiddleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Plugin class
 */
class Plugin extends PluginBase
{
    /**
     * register method, called when the plugin is first registered.
     */
    public function register()
    {
        $this->registerConsoleCommand(
            'quiz.dispatch.cleanup',
            \Beto\Quizwebapp\Console\DispatchCleanupJob::class
        );
    }

    /**
     * boot method, called right before the request route.
     */
    public function boot()
    {
        /** @var HttpKernel $kernel */
        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);

        $kernel->pushMiddleware(CustomCorsMiddleware::class);

        User::extend(function ($model) {
            $model->hasMany['quizzes'] = [Quiz::class, 'key' => 'author_id'];
        });

        RateLimiter::for('login', function ($request) {
            $email = (string) $request->input('email');

            return Limit::perMinute(5)->by(
                strtolower($email) . '|' . $request->ip()
            );
        });
    }

    /**
     * registerComponents used by the frontend.
     */
    public function registerComponents()
    {
    }

    /**
     * registerSettings used by the backend.
     */
    public function registerSettings()
    {
    }
}
