<?php
namespace Beto\Quizwebapp;

use Illuminate\Foundation\Http\Kernel as HttpKernel;
use System\Classes\PluginBase;
use RainLab\User\Models\User;
use Beto\Quizwebapp\Models\Quiz;
use Beto\Quizwebapp\Http\Middleware\CustomCorsMiddleware;

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
    }

    /**
     * boot method, called right before the request route.
     */
    public function boot()
    {
        /** @var HttpKernel $kernel */
        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
        $kernel->prependMiddleware(CustomCorsMiddleware::class);

        User::extend(function ($model) {
            $model->hasMany['quizzes'] = [Quiz::class, 'key' => 'author_id'];
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
