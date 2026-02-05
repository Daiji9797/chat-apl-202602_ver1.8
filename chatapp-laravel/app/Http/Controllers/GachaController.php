<?php

namespace App\Http\Controllers;

use App\Models\GachaImage;
use App\Models\GachaStatus;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class GachaController extends Controller
{
    public function execute(Request $request)
    {
        $userId = (int) $request->attributes->get('userId');
        $input = $request->json()->all();

        $gachaType = (int) ($input['gacha_type'] ?? 0);
        $pointsUsed = (int) ($input['points_used'] ?? 0);
        $gachaId = (int) ($input['gacha_id'] ?? 0);
        $resultStage = (int) ($input['result_stage'] ?? 1);

        if (!isset($input['gacha_type']) || !isset($input['points_used'])) {
            return ApiResponse::error('Missing required parameters: gacha_type, points_used', 400);
        }

        if (!in_array($pointsUsed, [10, 1000])) {
            return ApiResponse::error('Invalid points value. Must be 10 or 1000', 400);
        }

        $user = User::find($userId);

        if (!$user) {
            return ApiResponse::notFound('User not found');
        }

        if ((int) $user->points < $pointsUsed) {
            return ApiResponse::error('Insufficient points', 400);
        }

        $previousPoints = (int) $user->points;
        $newPoints = $previousPoints - $pointsUsed;

        $user->update(['points' => $newPoints]);

        GachaStatus::updateOrCreate(
            ['user_id' => $userId, 'gacha_id' => $gachaId],
            ['stage' => $resultStage]
        );

        return ApiResponse::success([
            'success' => true,
            'message' => 'Gacha executed successfully',
            'gacha_type' => $gachaType,
            'gacha_id' => $gachaId,
            'points_used' => $pointsUsed,
            'result_stage' => $resultStage,
            'previous_points' => $previousPoints,
            'current_points' => $newPoints,
            'user' => [
                'id' => $userId,
                'points' => $newPoints,
            ]
        ]);
    }

    public function getStatuses(Request $request)
    {
        $userId = (int) $request->attributes->get('userId');

        $gachaStatuses = GachaStatus::where('user_id', $userId)
            ->orderBy('gacha_id')
            ->get()
            ->map(fn($status) => [
                'gacha_id' => $status->gacha_id,
                'stage' => $status->stage,
            ]);

        return ApiResponse::success($gachaStatuses, 'Gacha statuses retrieved successfully');
    }

    public function getImages(Request $request)
    {
        $images = GachaImage::all()
            ->map(fn($image) => [
                'gacha_id' => $image->gacha_id,
                'stage' => $image->stage,
                'filename' => $image->filename,
                'image_path' => $image->image_path,
            ]);

        return ApiResponse::success($images, 'Gacha images retrieved successfully');
    }
}
