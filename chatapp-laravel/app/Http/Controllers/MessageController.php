<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Room;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function store(Request $request)
    {
        $userId = (int) $request->attributes->get('userId');
        $input = $request->json()->all();
        $roomId = (int) ($input['room_id'] ?? 0);
        $content = (string) ($input['content'] ?? '');
        $isAi = (bool) ($input['is_ai'] ?? false);

        if ($roomId <= 0 || $content === '') {
            return ApiResponse::error('Room ID and content are required', 400);
        }

        $room = Room::where('id', $roomId)->where('delete_flag', false)->first();

        if (!$room || $room->user_id !== $userId) {
            return ApiResponse::forbidden('You do not have access to this room');
        }

        $message = Message::create([
            'room_id' => $roomId,
            'user_id' => $userId,
            'is_ai' => $isAi,
            'content' => $content,
        ]);

        return ApiResponse::success([
            'id' => $message->id,
            'room_id' => $message->room_id,
            'user_id' => $message->user_id,
            'is_ai' => $message->is_ai,
            'content' => $message->content,
            'like_count' => 0,
            'liked_by_me' => false,
            'created_at' => $message->created_at?->toDateTimeString(),
            'updated_at' => $message->updated_at?->toDateTimeString(),
        ], 'Message created successfully', 201);
    }

    public function destroy(Request $request, int $id)
    {
        $userId = (int) $request->attributes->get('userId');
        $messageId = $id;
        $roomId = (int) $request->query('roomId');

        if ($messageId <= 0 || $roomId <= 0) {
            return ApiResponse::error('Invalid parameters', 400);
        }

        $room = Room::where('id', $roomId)
            ->where('delete_flag', false)
            ->first();

        if (!$room || $room->user_id !== $userId) {
            return ApiResponse::forbidden('You do not have access to this room');
        }

        $message = Message::where('id', $messageId)
            ->where('room_id', $roomId)
            ->first();

        if (!$message) {
            return ApiResponse::notFound('Message not found');
        }

        $message->delete();

        return ApiResponse::success(null, 'Message deleted');
    }
}
