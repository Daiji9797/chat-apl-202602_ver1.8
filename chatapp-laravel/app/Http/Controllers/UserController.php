<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ApiResponse;
use App\Support\PasswordPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function me(Request $request)
    {
        $userId = (int) $request->attributes->get('userId');

        $user = User::find($userId);

        if (!$user) {
            return ApiResponse::error('User not found', 404);
        }

        // stalker_image が Base64 形式でない場合は null にする
        $stalkerImage = $user->stalker_image;
        if ($stalkerImage && !str_starts_with($stalkerImage, 'data:')) {
            $stalkerImage = null;
        }

        return ApiResponse::success([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'is_admin' => (bool) ($user->is_admin ?? false),
                'points' => (int) ($user->points ?? 0),
                'last_login_date' => $user->last_login_date?->format('Y-m-d'),
                'stalker_image' => $stalkerImage,
            ],
        ], 'User info retrieved successfully', 200);
    }

    public function changePassword(Request $request)
    {
        $userId = (int) $request->attributes->get('userId');

        $input = $request->json()->all();
        $currentPassword = (string) ($input['current_password'] ?? '');
        $newPassword = (string) ($input['new_password'] ?? '');

        if ($currentPassword === '' || $newPassword === '') {
            return ApiResponse::error('Current password and new password are required', 400);
        }

        $user = User::find($userId);

        if (!$user) {
            return ApiResponse::notFound('User not found');
        }

        if (!Hash::check($currentPassword, (string) $user->password)) {
            return ApiResponse::error('Current password is incorrect', 401);
        }

        if ($currentPassword === $newPassword) {
            return ApiResponse::error('New password must be different from current password', 400);
        }

        $passwordError = PasswordPolicy::validate($newPassword, $user->email);
        if ($passwordError) {
            return ApiResponse::error($passwordError, 400);
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        return ApiResponse::success(['message' => 'Password changed successfully'], 'Password changed successfully', 200);
    }

    public function deleteAccount(Request $request)
    {
        $userId = (int) $request->attributes->get('userId');

        $input = $request->json()->all();
        $password = (string) ($input['password'] ?? '');

        if ($password === '') {
            return ApiResponse::error('Password is required for account deletion', 400);
        }

        $user = User::find($userId);

        if (!$user) {
            return ApiResponse::notFound('User not found');
        }

        if (!Hash::check($password, (string) $user->password)) {
            return ApiResponse::error('Invalid password', 401);
        }

        DB::transaction(function () use ($userId) {
            DB::table('message_likes')->where('user_id', $userId)->delete();
            DB::table('gacha_status')->where('user_id', $userId)->delete();
            DB::table('goal_notes')->where('user_id', $userId)->delete();
            DB::table('rooms')->where('user_id', $userId)->delete();
            DB::table('users')->where('id', $userId)->delete();
        });

        return ApiResponse::success(null, 'Account deleted successfully');
    }

    public function uploadStalkerImage(Request $request)
    {
        $userId = (int) $request->attributes->get('userId');

        if (!$request->hasFile('stalkerImage')) {
            return ApiResponse::error('Stalker image file is required', 400);
        }

        $file = $request->file('stalkerImage');

        // ファイルサイズチェック（5MB以下）
        if ($file->getSize() > 5 * 1024 * 1024) {
            return ApiResponse::error('Image size must be less than 5MB', 400);
        }

        // 画像をBase64エンコード
        $imageData = file_get_contents($file->getRealPath());
        $base64 = base64_encode($imageData);
        $mimeType = $file->getMimeType();
        $base64Image = 'data:' . $mimeType . ';base64,' . $base64;

        // ユーザーの stalker_image を更新
        $user = User::find($userId);
        if (!$user) {
            return ApiResponse::error('User not found', 404);
        }

        $user->stalker_image = $base64Image;
        $user->save();

        return ApiResponse::success([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'is_admin' => (bool) ($user->is_admin ?? false),
                'points' => (int) ($user->points ?? 0),
                'last_login_date' => $user->last_login_date?->format('Y-m-d'),
                'stalker_image' => $user->stalker_image,
            ],
        ], 'Stalker image uploaded successfully');
    }

    public function deleteStalkerImage(Request $request)
    {
        $userId = (int) $request->attributes->get('userId');

        $user = User::find($userId);
        if (!$user) {
            return ApiResponse::error('User not found', 404);
        }

        // stalker_image をリセット（Base64なのでファイル削除不要）
        $user->stalker_image = null;
        $user->save();

        return ApiResponse::success([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'is_admin' => (bool) ($user->is_admin ?? false),
                'points' => (int) ($user->points ?? 0),
                'last_login_date' => $user->last_login_date?->format('Y-m-d'),
                'stalker_image' => $user->stalker_image,
            ],
        ], 'Stalker image deleted successfully');
    }
}
