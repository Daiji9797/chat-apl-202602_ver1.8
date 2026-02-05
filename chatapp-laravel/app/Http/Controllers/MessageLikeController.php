<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageLike;
use App\Models\Room;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MessageLikeController extends Controller
{
    public function toggle(Request $request)
    {
        $userId = (int) $request->attributes->get('userId');
        $input = $request->json()->all();
        $messageId = (int) ($input['messageId'] ?? 0);
        $roomId = (int) ($input['roomId'] ?? 0);
        $like = (bool) ($input['like'] ?? true);

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

        if ($like) {
            MessageLike::firstOrCreate([
                'message_id' => $messageId,
                'user_id' => $userId,
            ]);
        } else {
            MessageLike::where('message_id', $messageId)
                ->where('user_id', $userId)
                ->delete();
        }

        $likeCount = MessageLike::where('message_id', $messageId)->count();
        $likedByMe = MessageLike::where('message_id', $messageId)
            ->where('user_id', $userId)
            ->exists();

        return ApiResponse::success([
            'like_count' => $likeCount,
            'liked_by_me' => $likedByMe,
        ], 'Like status updated');
    }
}
