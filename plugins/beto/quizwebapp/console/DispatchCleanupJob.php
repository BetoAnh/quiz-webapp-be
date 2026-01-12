<?php
namespace Beto\Quizwebapp\Console;

use Illuminate\Console\Command;
use Beto\Quizwebapp\Jobs\CleanupTempDataJob;

class DispatchCleanupJob extends Command
{
    protected $name = 'cleanup:dispatch';
    protected $description = 'Dispatch CleanupTempDataJob to Redis queue';

    public function handle()
    {
        CleanupTempDataJob::dispatch()
            ->onQueue('maintenance');

        $this->info('CleanupTempDataJob dispatched');
    }
}
