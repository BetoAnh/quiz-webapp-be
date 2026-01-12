<?php
namespace Beto\Quizwebapp\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Beto\Quizwebapp\Models\QuizTemp;
use Illuminate\Foundation\Bus\Dispatchable;

class CleanupQuizTempJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 1;

    public function handle()
    {
        QuizTemp::whereIn('status', ['failed', 'expired'])
            ->where('updated_at', '<', now()->subDay())
            ->delete();

        QuizTemp::where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(30))
            ->update([
                'status' => 'expired'
            ]);
    }
}


