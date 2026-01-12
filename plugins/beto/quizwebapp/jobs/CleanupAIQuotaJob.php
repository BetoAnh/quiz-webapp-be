<?php
namespace Beto\Quizwebapp\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Beto\Quizwebapp\Models\AIQuota;

class CleanupAIQuotaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 1;

    public function handle()
    {
        Log::info('[CleanupAIQuotaJob] Bắt đầu dọn record ai_quota cũ');

        // Xóa tất cả record cũ hơn 1 ngày
        $cutoff = now()->subDay(); // hoặc subDays(2) nếu muốn giữ 2 ngày

        AIQuota::where('created_at', '<', $cutoff)
            ->chunkById(1000, function ($records) {
                $ids = $records->pluck('id')->toArray();
                AIQuota::whereIn('id', $ids)->delete();
                Log::info('[CleanupAIQuotaJob] Xóa ' . count($ids) . ' record ai_quota');
            });

        Log::info('[CleanupAIQuotaJob] Hoàn tất cleanup ai_quota');
    }
}
