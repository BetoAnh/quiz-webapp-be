<?php
namespace Beto\Quizwebapp\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Beto\Quizwebapp\Classes\QuizGenerator;
use Beto\Quizwebapp\Models\QuizTemp;
use Beto\Quizwebapp\Models\AIQuota;

use Throwable;

class GenerateQuizJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $timeout = 120; // tối đa 2 phút
    public $tries = 1;     // AI lỗi → fail luôn, không retry âm thầm

    protected int $quizTempId;
    protected string $text;
    protected ?int $numQuestions;
    protected ?string $validationWarning;

    public function __construct(
        int $quizTempId,
        string $text,
        ?int $numQuestions = null,
        ?string $validationWarning = null
    ) {
        $this->quizTempId = $quizTempId;
        $this->text = $text;
        $this->numQuestions = $numQuestions;
        $this->validationWarning = $validationWarning;
    }

    public function handle()
    {
        $quizTemp = QuizTemp::where('id', $this->quizTempId)
            ->where('status', 'pending')
            ->first();

        if (!$quizTemp) {
            \Log::warning("[GenerateQuizJob] QuizTemp not found", [
                'quiz_temp_id' => $this->quizTempId
            ]);
            return;
        }

        try {
            $result = QuizGenerator::fromText($this->text, $this->numQuestions);

            if (empty($result['quiz'])) {
                throw new \RuntimeException(
                    $result['warning'] ?? 'AI không trả về quiz'
                );
            }

            // ✅ Thành công
            $updated = AIQuota::where('user_id', $quizTemp->user_id)
                ->where('date', now()->toDateString())
                ->whereColumn('used', '<', 'limit')
                ->increment('used');

            if ($updated === 0) {
                throw new \RuntimeException('Hết quota AI hôm nay');
            }

            $quizTemp->data = $result['quiz'];
            $quizTemp->status = 'ready';

            $warnings = array_filter([
                $this->validationWarning,
                $result['warning'] ?? null
            ]);

            $quizTemp->warning = $warnings
                ? implode("\n", $warnings)
                : null;

            $quizTemp->save();

        } catch (Throwable $e) {

            // ❌ AI lỗi → mark failed
            $quizTemp->status = 'failed';
            $quizTemp->warning = '❌ Tạo quiz thất bại: ' . $e->getMessage();
            $quizTemp->save();

            \Log::error('[GenerateQuizJob] Failed', [
                'quiz_temp_id' => $this->quizTempId,
                'error' => $e->getMessage()
            ]);

            // Ném exception để Laravel biết job fail (log + metric)
            throw $e;
        }
    }
}
