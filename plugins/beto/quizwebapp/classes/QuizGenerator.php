<?php
namespace Beto\Quizwebapp\Classes;

use OpenAI;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;

class QuizGenerator
{
    /**
     * Giới hạn file
     */
    private const MAX_TEXT_LEN = 50000; // tối đa 50k ký tự (~12k tokens)
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
                'warning' => "⚠️ File có {$len} ký tự, chỉ xử lý 50.000 ký tự đầu tiên để tránh quá tải."
            ];
        }

        return ['valid' => true, 'text' => $text];
    }



    /**
     * Trích xuất nội dung text từ file (txt, pdf, docx)
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
    public static function fromText($text, $numQuestions = null)
    {
        // $client = OpenAI::client(env('OPENAI_API_KEY'));
        // $warning = null;

        $client = OpenAI::factory()
            ->withApiKey(env('OPENROUTER_API_KEY'))
            ->withBaseUri('https://openrouter.ai/api/v1')
            ->make();
        $warning = null;

        if (mb_strlen($text, 'UTF-8') > self::MAX_TEXT_LEN) {
            $text = mb_substr($text, 0, self::MAX_TEXT_LEN, 'UTF-8');
            $warning = "⚠️ Nội dung quá dài, đã cắt bớt để phù hợp giới hạn.";
        }
        // Prompt tạo quiz
        $prompt = !empty($numQuestions)
            ? "Phân tích và tạo khoảng $numQuestions câu hỏi trắc nghiệm từ nội dung sau
            (nếu thấy không đủ nội dung, có thể sinh ít hơn không nhất thiết phải đủ số lượng câu hỏi).
            Mỗi câu hỏi gồm:
            - text: nội dung câu hỏi
            - options: danh sách 4 lựa chọn, mỗi lựa chọn có 'id' (0-3) và 'text'
            - correctId: số thứ tự (0-3) của đáp án đúng

            CHỈ trả về JSON hợp lệ theo cấu trúc sau, KHÔNG ghi thêm mô tả, lời giải thích hoặc văn bản khác.
            Trả về JSON với cấu trúc:
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
            $text
            "
            : "Phân tích nội dung sau và tạo ra *một câu hỏi trắc nghiệm cho mỗi ý hoặc kiến thức độc lập có thể kiểm tra được*.
            Không bỏ sót thông tin nào đáng hỏi.
            Số lượng câu hỏi do bạn tự quyết định dựa trên nội dung.
            Mỗi câu hỏi gồm:
            - text
            - options: 4 lựa chọn có id từ 0–3
            - correctId: số thứ tự (0–3) của đáp án đúng

            CHỈ trả về JSON **hợp lệ** đúng theo cấu trúc sau, KHÔNG ghi giải thích, chú thích hay mô tả gì khác.
            Trả về JSON với cấu trúc:
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
            $text
            ";

        // Gọi OpenAI API
        // $response = $client->chat()->create([
        //     'model' => 'gpt-4o-mini',
        //     'messages' => [
        //         ['role' => 'user', 'content' => $prompt],
        //     ],
        //     'temperature' => 0.7,
        //     'max_tokens' => 4096,
        // ]);

        // ✅ Gọi OpenRouter (chọn model miễn phí)
        try {
            $response = $client->chat()->create([
                'model' => 'google/gemma-2-9b-it',  //hoặc 'google/gemma-2-9b-it', 'meta-llama/llama-3.1-8b-instruct'
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 4096,
            ]);
        } catch (\Exception $e) {
            return [
                'quiz' => null,
                'warning' => "❌ Lỗi gọi API: " . $e->getMessage()
            ];
        }

        $content = $response['choices'][0]['message']['content'] ?? '';
        $data = json_decode($content, true);

        if (!$data || !isset($data['questions'])) {
            $data = [
                'title' => '',
                'description' => '',
                'visibility' => 'public',
                'questions' => []
            ];
        }

        foreach ($data['questions'] as $i => &$q) {
            $q['id'] = $i;
            foreach ($q['options'] ?? [] as $j => &$opt) {
                $opt['id'] = $j;
            }
        }

        if (!empty($numQuestions)) {
            $actual = count($data['questions']);
            if ($actual < $numQuestions) {
                $warning .= "\n⚠️ Chỉ tạo được $actual/$numQuestions câu hỏi (tài liệu có thể quá ngắn hoặc không đủ nội dung).";
            }
        }

        return [
            'quiz' => $data,
            'warning' => $warning
        ];
    }
}
