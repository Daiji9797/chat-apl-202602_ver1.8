<?php

namespace App\Support;

use Exception;

class GeminiService
{
    public static function callAPI(array $messages): string
    {
        $apiKey = env('GEMINI_API_KEY');
        if (empty($apiKey)) {
            \Log::error('GEMINI_API_KEY is not configured');
            throw new Exception('Gemini API key not configured');
        }

        $systemPrompt = 'あなたは親切で知識豊富なアシスタントです。ユーザーの質問に対して、常に新しく、思慮深い、かつユニークな回答を提供してください。単調または繰り返しの回答は避けてください。異なる視点や具体的な例を含めるようにしてください。常に相手の状況や背景を考慮し、より有用で詳細な回答を心がけてください。

【重要な制限事項】
以下のコンテンツに関する質問や要求には一切応じないでください：
- グラフィックな暴力や残虐な内容
- 未成年者に危険または有害な行動を促す可能性のあるバイラルチャレンジ
- 性的、恋愛的、または暴力的なロールプレイ
- 自傷行為の描写
- 極端な美容基準、不健康なダイエット、ボディシェイミングを助長するコンテンツ

これらの内容に該当する質問には、「申し訳ございませんが、その質問にはお答えできません。別の質問がございましたら、お気軽にお尋ねください。」と丁寧に断ってください。';

        $contents = [];
        $lastRole = null;

        foreach ($messages as $msg) {
            if (isset($msg['role']) && isset($msg['content'])) {
                $currentRole = ($msg['role'] === 'assistant' || $msg['role'] === 'bot') ? 'model' : 'user';

                if ($lastRole === $currentRole) {
                    continue;
                }

                $contents[] = [
                    'role' => $currentRole,
                    'parts' => [['text' => $msg['content']]]
                ];
                $lastRole = $currentRole;
            }
        }

        // 最後が user でない場合は追加しない（エラー回避）
        if ($lastRole !== 'user' && !empty($contents)) {
            // OK
        } elseif (empty($contents)) {
            // 履歴がない場合はスキップ
        }

        $ch = curl_init();

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;

        $postData = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.85,
                'topP' => 0.9,
                'maxOutputTokens' => 2000
            ]
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($postData)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($curlError) {
            \Log::error('Gemini CURL Error', ['error' => $curlError]);
            throw new Exception('Network error: ' . $curlError);
        }

        if ($httpCode !== 200) {
            \Log::error('Gemini API Error', ['httpCode' => $httpCode, 'response' => $response]);
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? $response;
            throw new Exception('Gemini API error (HTTP ' . $httpCode . '): ' . $errorMessage);
        }

        $responseData = json_decode($response, true);

        if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            \Log::error('Gemini Invalid Response', ['response' => $responseData]);
            return '申し訳ありませんが、適切な応答を生成できませんでした。';
        }

        return $responseData['candidates'][0]['content']['parts'][0]['text'];
    }
}
