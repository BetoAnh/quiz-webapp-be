<?php
namespace Beto\Quizwebapp\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Beto\Quizwebapp\Models\QuizTemp;
use Beto\Quizwebapp\Models\AIQuota;

class CleanupTempDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 phút, tăng nếu table nhiều record
    public $tries = 1;

    public function handle()
    {
        Log::info('[CleanupTempDataJob] Bắt đầu cleanup quiz_temp + ai_quota');

        // --- CLEANUP quiz_temp ---
        // Xóa record failed/expired cũ hơn 1 ngày
        $quizCutoff = now()->subDay();
        QuizTemp::whereIn('status', ['failed', 'expired'])
            ->where('updated_at', '<', $quizCutoff)
            ->chunkById(1000, function ($records) {
                $ids = $records->pluck('id')->toArray();
                QuizTemp::whereIn('id', $ids)->delete();
                Log::info('[CleanupTempDataJob] Xóa quiz_temp failed/expired: ' . count($ids));
            });

        // Update record pending >30 phút thành expired
        QuizTemp::where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(30))
            ->chunkById(1000, function ($records) {
                $ids = $records->pluck('id')->toArray();
                QuizTemp::whereIn('id', $ids)->update(['status' => 'expired']);
                Log::info('[CleanupTempDataJob] Cập nhật quiz_temp pending >30 phút: ' . count($ids));
            });

        // --- CLEANUP ai_quota ---
        $quotaCutoff = now()->subDay(); // xóa tất cả record cũ hơn 1 ngày
        AIQuota::where('created_at', '<', $quotaCutoff)
            ->chunkById(1000, function ($records) {
                $ids = $records->pluck('id')->toArray();
                AIQuota::whereIn('id', $ids)->delete();
                Log::info('[CleanupTempDataJob] Xóa ai_quota cũ: ' . count($ids));
            });

        Log::info('[CleanupTempDataJob] Hoàn tất cleanup quiz_temp + ai_quota');
    }
}
