<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Room;
use App\Support\ApiResponse;
use App\Support\OpenAIService;
use App\Support\GeminiService;
use Exception;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function chat(Request $request)
    {
        $userId = (int) $request->attributes->get('userId');
        $input = $request->json()->all();

        $message = trim((string) ($input['message'] ?? ''));
        $roomId = (int) ($input['roomId'] ?? 0);
        $providedHistory = $input['history'] ?? null;
        $provider = strtolower((string) ($input['provider'] ?? 'openai'));

        if (!in_array($provider, ['openai', 'gemini'])) {
            $provider = 'openai';
        }

        if ($roomId <= 0 || $message === '') {
            return ApiResponse::error('Message and roomId are required', 400);
        }

        try {
            $room = Room::where('id', $roomId)
                ->where('delete_flag', false)
                ->first();

            if (!$room || $room->user_id !== $userId) {
                return ApiResponse::forbidden('You do not have access to this room');
            }

            // ユーザーメッセージを保存
            Message::create([
                'room_id' => $roomId,
                'sender' => 'user',
                'text' => $message,
            ]);

            // API 呼び出し用のメッセージ配列を準備
            $messages = [];

            if (is_array($providedHistory) && !empty($providedHistory)) {
                foreach ($providedHistory as $histItem) {
                    if (isset($histItem['role']) && isset($histItem['content'])) {
                        $messages[] = [
                            'role' => $histItem['role'],
                            'content' => $histItem['content']
                        ];
                    }
                }
            }

            $messages[] = [
                'role' => 'user',
                'content' => $message
            ];

            // AI 応答を取得（Gemini API キーがない場合は OpenAI を使用）
            $geminiApiKey = env('GEMINI_API_KEY');
            if ($provider === 'gemini' && !empty($geminiApiKey)) {
                $aiResponse = GeminiService::callAPI($messages);
            } else {
                $aiResponse = OpenAIService::callAPI($messages);
            }

            if (!$aiResponse) {
                return ApiResponse::error('Failed to get AI response', 500);
            }

            // AI メッセージを保存
            Message::create([
                'room_id' => $roomId,
                'sender' => 'bot',
                'text' => $aiResponse,
            ]);

            return ApiResponse::success([
                'response' => $aiResponse
            ], 'Message processed successfully', 200);

        } catch (Exception $e) {
            \Log::error('Chat error', ['error' => $e->getMessage()]);
            return ApiResponse::error('Internal server error', 500);
        }
    }
}
