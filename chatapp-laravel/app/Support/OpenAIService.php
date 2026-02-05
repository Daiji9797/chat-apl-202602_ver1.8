<?php

namespace App\Support;

use Exception;

class OpenAIService
{
    public static function callAPI(array $messages): string
    {
        $apiKey = env('OPENAI_API_KEY');
        if (empty($apiKey)) {
            \Log::error('OPENAI_API_KEY is not configured');
            throw new Exception('OpenAI API key not configured');
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

        $allMessages = [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ]
        ];

        foreach ($messages as $msg) {
            if (isset($msg['role']) && isset($msg['content'])) {
                $allMessages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content']
                ];
            }
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => $allMessages,
                'temperature' => 0.85,
                'top_p' => 0.9,
                'frequency_penalty' => 1.0,
                'presence_penalty' => 0.5,
                'max_tokens' => 2000
            ])
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($curlError) {
            \Log::error('OpenAI CURL Error', ['error' => $curlError]);
            throw new Exception('Network error: ' . $curlError);
        }

        if ($httpCode !== 200) {
            \Log::error('OpenAI API Error', ['httpCode' => $httpCode, 'response' => $response]);
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? 'Unknown error';
            throw new Exception('OpenAI API error (HTTP ' . $httpCode . '): ' . $errorMessage);
        }

        $responseData = json_decode($response, true);

        if (!isset($responseData['choices'][0]['message']['content'])) {
            \Log::error('OpenAI Invalid Response', ['response' => $responseData]);
            throw new Exception('Invalid response structure from OpenAI API');
        }

        return $responseData['choices'][0]['message']['content'];
    }
}
