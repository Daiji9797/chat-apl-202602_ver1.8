<?php

namespace App\Http\Controllers;

use App\Models\GoalNote;
use App\Models\Room;
use App\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) $request->attributes->get('userId');
        $roomId = (int) ($request->query('roomId') ?? 0);
        $limit = (int) ($request->query('limit') ?? 50);
        $offset = (int) ($request->query('offset') ?? 0);

        $query = GoalNote::where('user_id', $userId)->goalNotes();

        if ($roomId > 0) {
            $query = $query->where('room_id', $roomId);
        }

        $notes = $query->orderByDesc('created_at')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(fn($note) => [
                'id' => $note->id,
                'user_id' => $note->user_id,
                'room_id' => $note->room_id,
                'message_id' => $note->message_id,
                'note_text' => $note->note_text,
                'created_at' => $note->created_at,
            ]);

        return ApiResponse::success($notes, 'Goal notes retrieved successfully');
    }

    public function store(Request $request)
    {
        $userId = (int) $request->attributes->get('userId');
        $input = $request->json()->all();

        $noteText = trim((string) ($input['note_text'] ?? ''));
        $roomId = (int) ($input['room_id'] ?? 0);
        $messageId = (int) ($input['message_id'] ?? null);

        if ($noteText === '' || $roomId <= 0) {
            return ApiResponse::error('note_text and room_id are required', 400);
        }

        $room = Room::where('id', $roomId)->where('user_id', $userId)->first();

        if (!$room) {
            return ApiResponse::forbidden('You do not have access to this room');
        }

        $note = GoalNote::create([
            'user_id' => $userId,
            'room_id' => $roomId,
            'message_id' => $messageId ?: null,
            'note_text' => $noteText,
            'created_at' => now()->toDateTimeString(),
        ]);

        return ApiResponse::success([
            'id' => $note->id,
            'user_id' => $note->user_id,
            'room_id' => $note->room_id,
            'message_id' => $note->message_id,
            'note_text' => $note->note_text,
            'created_at' => $note->created_at,
        ], 'Goal note created successfully', 201);
    }

    public function update(Request $request)
    {
        $userId = (int) $request->attributes->get('userId');
        $input = $request->json()->all();

        $noteId = (int) ($input['id'] ?? 0);
        $noteText = isset($input['note_text']) ? trim((string) $input['note_text']) : null;

        if ($noteId <= 0) {
            return ApiResponse::error('id is required', 400);
        }

        $note = GoalNote::where('id', $noteId)->where('user_id', $userId)->first();

        if (!$note) {
            return ApiResponse::notFound('Goal note not found');
        }

        if ($noteText !== null) {
            $note->note_text = $noteText;
        }

        $note->save();

        return ApiResponse::success([
            'id' => $note->id,
            'note_text' => $note->note_text,
        ], 'Goal note updated successfully');
    }

    public function destroy(Request $request)
    {
        $userId = (int) $request->attributes->get('userId');
        $input = $request->json()->all();

        $noteId = (int) ($input['id'] ?? $request->query('id') ?? 0);

        if ($noteId <= 0) {
            return ApiResponse::error('id is required', 400);
        }

        $note = GoalNote::where('id', $noteId)->where('user_id', $userId)->first();

        if (!$note) {
            return ApiResponse::notFound('Goal note not found');
        }

        $note->delete();

        return ApiResponse::success(null, 'Goal note deleted successfully');
    }
}
