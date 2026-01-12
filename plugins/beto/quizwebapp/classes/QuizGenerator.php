<?php
namespace Beto\Quizwebapp\Classes;

use Illuminate\Support\Facades\Http;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;
class QuizGenerator
{
    /**
     * Giới hạn nội dung
     */
    private const MAX_TEXT_LEN = 50000; // tối đa 50k ký tự
    private const MIN_TEXT_LEN = 100;   // tối thiểu để tránh file rỗng

    /**
     * Kiểm tra file upload hợp lệ (định dạng, dung lượng, text)
     */
    public static function validateFile($file)
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();

        $allowedExt = ['pdf', 'docx', 'txt'];
        $allowedMime = [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain'
        ];

        if (!in_array($ext, $allowedExt) || !in_array($mime, $allowedMime)) {
            return [
                'valid' => false,
                'error' => "❌ Định dạng không hợp lệ ($ext | $mime). Chỉ hỗ trợ PDF, DOCX hoặc TXT."
            ];
        }

        $path = $file->getPathname();
        $text = trim(self::extractText($path, $ext));

        $len = mb_strlen($text, 'UTF-8');
        if ($len < self::MIN_TEXT_LEN) {
            return [
                'valid' => false,
                'error' => "❌ File quá ngắn hoặc không có nội dung hợp lệ."
            ];
        }

        if ($len > self::MAX_TEXT_LEN) {
            $text = mb_substr($text, 0, self::MAX_TEXT_LEN, 'UTF-8');
            return [
                'valid' => true,
                'text' => $text,
                'warning' => "⚠️ File có {$len} ký tự, chỉ xử lý 50.000 ký tự đầu tiên."
            ];
        }

        return [
            'valid' => true,
            'text' => $text
        ];
    }

    /**
     * Trích xuất text từ file
     */
    public static function extractText($path, $ext)
    {
        switch ($ext) {
            case 'txt':
                return file_get_contents($path);

            case 'pdf':
                $parser = new Parser();
                $pdf = $parser->parseFile($path);
                return $pdf->getText();

            case 'docx':
                $phpWord = IOFactory::load($path);
                $text = '';
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            $text .= $element->getText() . "\n";
                        }
                    }
                }
                return $text;

            default:
                return '';
        }
    }

    /**
     * Tạo quiz từ text bằng Gemini
     */
    public static function fromText($text, $numQuestions = null)
    {
        \Log::info('🚀 [QuizGenerator] Bắt đầu tạo quiz', [
            'text_length' => mb_strlen($text, 'UTF-8'),
            'numQuestions' => $numQuestions
        ]);

        try {
            $warning = null;

            if (mb_strlen($text, 'UTF-8') > self::MAX_TEXT_LEN) {
                $text = mb_substr($text, 0, self::MAX_TEXT_LEN, 'UTF-8');
                $warning = "⚠️ Nội dung quá dài, đã cắt bớt để phù hợp giới hạn.";
            }

            // Prompt tạo quiz
            $prompt = !empty($numQuestions)
                ? "Phân tích và tạo khoảng $numQuestions câu hỏi trắc nghiệm từ nội dung sau
(nếu thấy không đủ nội dung, có thể sinh ít hơn).
Mỗi câu hỏi gồm:
- text: nội dung câu hỏi
- options: danh sách 4 lựa chọn, mỗi lựa chọn có 'id' (0-3) và 'text'
- correctId: số thứ tự (0-3) của đáp án đúng

CHỈ trả về JSON hợp lệ theo cấu trúc sau, KHÔNG ghi thêm mô tả, lời giải thích hay văn bản khác.

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

--- Nội dung ---
$text"
                : "Phân tích nội dung sau và tạo một câu hỏi trắc nghiệm cho mỗi ý hoặc kiến thức độc lập có thể kiểm tra được.
Không bỏ sót thông tin quan trọng.
Số lượng câu hỏi do bạn tự quyết định.

Mỗi câu hỏi gồm:
- text
- options: 4 lựa chọn có id từ 0–3
- correctId: số thứ tự (0–3) của đáp án đúng

CHỈ trả về JSON hợp lệ theo cấu trúc sau, KHÔNG ghi thêm bất kỳ nội dung nào khác.

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

--- Nội dung ---
$text";

            // Gọi Gemini API
            $response = Http::timeout(60)->post(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . env('GEMINI_API_KEY'),
                [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.4,
                        'maxOutputTokens' => 4096,
                        'response_mime_type' => 'application/json' // 🔥 CỰC QUAN TRỌNG
                    ]
                ]
            );

            if ($response->status() == 429) {
                return [
                    'quiz' => null,
                    'warning' => "❌ AI đã quá tải hoặc vượt giới hạn, vui lòng thử lại sau."
                ];
            }

            if ($response->status() >= 500) {
                return [
                    'quiz' => null,
                    'warning' => "❌ AI đang gặp sự cố, vui lòng thử lại sau."
                ];
            }

            $result = $response->json();

            $content = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

            $data = json_decode($content, true);

            if (!is_array($data) || !isset($data['questions'])) {
                return [
                    'quiz' => null,
                    'warning' => '❌ AI không trả về JSON hợp lệ.'
                ];
            }

            // Chuẩn hóa id
            foreach ($data['questions'] as $i => &$q) {
                $q['id'] = $i;
                foreach ($q['options'] ?? [] as $j => &$opt) {
                    $opt['id'] = $j;
                }
            }

            if (!empty($numQuestions)) {
                $actual = count($data['questions']);
                if ($actual < $numQuestions) {
                    $warning .= "\n⚠️ Chỉ tạo được $actual/$numQuestions câu hỏi.";
                }
            }

            return [
                'quiz' => $data,
                'warning' => $warning
            ];

        } catch (\Throwable $e) {
            \Log::error('💥 [QuizGenerator] Lỗi khi gọi Gemini', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'quiz' => null,
                'warning' => "❌ Lỗi AI: " . $e->getMessage()
            ];
        }
    }
}
