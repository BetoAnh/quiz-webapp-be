<?php
namespace Beto\Quizwebapp\Classes;

use Illuminate\Support\Facades\Http;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;

class QuizGenerator
{
    /** ================= CONFIG ================= */

    private const MAX_TEXT_LEN = 50000;
    private const MIN_TEXT_LEN = 100;
    private const MAX_FILE_SIZE = 2097152;

    // AI tuning
    private const DEFAULT_QUESTIONS = 10;
    private const MAX_QUESTIONS = 10;
    private const MAX_AI_INPUT = 15000;

    /** ================= TEXT UTILS ================= */

    private static function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        $lines = array_filter(
            array_map('trim', explode("\n", $text)),
            fn($line) => preg_match('/[\p{L}\p{N}]/u', $line)
        );

        $text = implode("\n", $lines);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    private static function isMostlyText(string $text): bool
    {
        $len = mb_strlen($text, 'UTF-8');
        if ($len === 0)
            return false;

        $alphaNum = preg_match_all('/[\p{L}\p{N}]/u', $text);
        return ($alphaNum / $len) > 0.3;
    }

    private static function cleanJson(string $raw): string
    {
        $raw = trim($raw);

        if (str_starts_with($raw, '{') && str_ends_with($raw, '}')) {
            return $raw;
        }

        $start = strpos($raw, '{');
        if ($start === false)
            return '';

        $raw = substr($raw, $start);
        $end = strrpos($raw, '}');

        if ($end !== false) {
            $raw = substr($raw, 0, $end + 1);
        }

        return trim($raw);
    }

    /** ================= FILE VALIDATION ================= */

    public static function validateFile($file)
    {
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return ['valid' => false, 'error' => "❌ File quá lớn (tối đa 2MB)."];
        }

        $ext = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();

        $allowedExt = ['pdf', 'docx', 'txt'];
        $allowedMime = [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain'
        ];

        if (!in_array($ext, $allowedExt) || !in_array($mime, $allowedMime)) {
            return ['valid' => false, 'error' => "❌ Chỉ hỗ trợ PDF, DOCX, TXT."];
        }

        $text = trim(self::extractText($file->getPathname(), $ext));

        if (mb_strlen($text, 'UTF-8') < self::MIN_TEXT_LEN) {
            return ['valid' => false, 'error' => "❌ File không có đủ nội dung chữ."];
        }

        if (!self::isMostlyText($text)) {
            return ['valid' => false, 'error' => "❌ File có vẻ là scan hoặc không phải văn bản."];
        }

        $text = self::normalizeText($text);

        if (mb_strlen($text, 'UTF-8') < self::MIN_TEXT_LEN) {
            return ['valid' => false, 'error' => "❌ Nội dung không đủ để tạo quiz."];
        }

        if (mb_strlen($text, 'UTF-8') > self::MAX_TEXT_LEN) {
            $text = mb_substr($text, 0, self::MAX_TEXT_LEN, 'UTF-8');
            return ['valid' => true, 'text' => $text, 'warning' => "⚠️ Nội dung đã bị cắt bớt."];
        }

        return ['valid' => true, 'text' => $text];
    }

    /** ================= TEXT EXTRACT ================= */

    public static function extractText($path, $ext)
    {
        return match ($ext) {
            'txt' => file_get_contents($path),
            'pdf' => (new Parser())->parseFile($path)->getText(),
            'docx' => self::extractDocx($path),
            default => ''
        };
    }

    private static function extractDocx($path): string
    {
        $phpWord = IOFactory::load($path);
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $el) {
                if (method_exists($el, 'getText')) {
                    $text .= $el->getText() . "\n";
                }
                if (method_exists($el, 'getElements')) {
                    foreach ($el->getElements() as $child) {
                        if (method_exists($child, 'getText')) {
                            $text .= $child->getText() . "\n";
                        }
                    }
                }
            }
        }
        return $text;
    }

    /** ================= AI GENERATION ================= */

    public static function fromText(string $text, $numQuestions = null)
    {
        try {
            $textLength = mb_strlen($text, 'UTF-8');

            // Giảm input để tránh timeout
            if ($textLength > self::MAX_AI_INPUT) {
                $text = mb_substr($text, 0, self::MAX_AI_INPUT, 'UTF-8');
            }

            // Tính số câu hỏi hiệu lực
            $effectiveNumQuestions = is_numeric($numQuestions) && $numQuestions > 0
                ? (int) $numQuestions
                : self::DEFAULT_QUESTIONS;

            // hard cap
            $effectiveNumQuestions = min(
                self::MAX_QUESTIONS,
                $effectiveNumQuestions
            );

            $prompt = "
Mục tiêu là tạo TỐI ĐA {$effectiveNumQuestions} câu hỏi trắc nghiệm
từ nội dung được cung cấp.

- Nếu nội dung đủ: tạo CHÍNH XÁC {$effectiveNumQuestions} câu.
- Nếu nội dung KHÔNG đủ: tạo ÍT HƠN, nhưng TUYỆT ĐỐI KHÔNG vượt quá {$effectiveNumQuestions} câu.
- Không được tự ý tạo thêm thông tin ngoài nội dung cho sẵn.

Mỗi câu hỏi gồm:
- text: nội dung câu hỏi
- options: đúng 4 lựa chọn, mỗi lựa chọn có 'id' (0–3) và 'text'
- correctId: số (0–3)

YÊU CẦU BẮT BUỘC:
- CHỈ trả về JSON hợp lệ
- KHÔNG markdown
- KHÔNG giải thích
- KHÔNG thêm bất kỳ văn bản nào khác
- Nếu không thể tạo được ít nhất 1 câu hỏi hợp lệ, trả về {}

Cấu trúc JSON:
{
  \"title\": \"\",
  \"description\": \"\",
  \"visibility\": \"public\",
  \"questions\": [
    {
      \"id\": 0,
      \"text\": \"...\",
      \"options\": [
        { \"id\": 0, \"text\": \"...\" },
        { \"id\": 1, \"text\": \"...\" },
        { \"id\": 2, \"text\": \"...\" },
        { \"id\": 3, \"text\": \"...\" }
      ],
      \"correctId\": 0
    }
  ]
}

Nội dung:
$text
";

            $response = Http::timeout(40)
                ->retry(2, 500)
                ->post(
                    'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . env('GEMINI_API_KEY'),
                    [
                        'contents' => [
                            [
                                'role' => 'user',
                                'parts' => [['text' => $prompt]]
                            ]
                        ],
                        'generationConfig' => [
                            'temperature' => 0.3,
                            'maxOutputTokens' => 8192,
                            'response_mime_type' => 'application/json'
                        ]
                    ]
                );

            $raw = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $clean = self::cleanJson($raw);

            $data = json_decode($clean, true);

            if (!is_array($data) || !isset($data['questions'])) {
                \Log::warning('🧨 Invalid JSON from Gemini', ['raw' => $raw]);
                return ['quiz' => null, 'warning' => '❌ AI không trả về JSON hợp lệ.'];
            }

            foreach ($data['questions'] as $i => &$q) {
                $q['id'] = $i;
                foreach ($q['options'] ?? [] as $j => &$opt) {
                    $opt['id'] = $j;
                }
            }

            return ['quiz' => $data, 'warning' => null];

        } catch (\Throwable $e) {
            \Log::error('💥 [QuizGenerator]', [
                'error' => $e->getMessage()
            ]);

            return ['quiz' => null, 'warning' => '❌ Lỗi AI, vui lòng thử lại.'];
        }
    }
}
