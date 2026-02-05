<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ApiResponse;
use App\Support\LegacyJwt;
use App\Support\PasswordPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $input = $request->json()->all();

        if (!isset($input['email']) || !isset($input['password'])) {
            return ApiResponse::error('Email and password are required', 400);
        }

        $email = trim((string) $input['email']);
        $password = trim((string) $input['password']);

        $user = User::where('email', $email)->first();

        if (!$user) {
            return ApiResponse::error('メールアドレスまたはパスワードが正しくありません', 400);
        }

        if (!Hash::check($password, (string) $user->password)) {
            return ApiResponse::error('メールアドレスまたはパスワードが正しくありません', 400);
        }

        $this->addLoginPoints($user);

        $token = LegacyJwt::generateToken((int) $user->id);

        return ApiResponse::success([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'is_admin' => (bool) ($user->is_admin ?? false),
            ],
            'token' => $token,
        ], 'Logged in successfully', 200);
    }

    public function register(Request $request)
    {
        $input = $request->json()->all();

        if (!isset($input['email']) || !isset($input['password'])) {
            return ApiResponse::error('Email and password are required', 400);
        }

        $email = trim((string) $input['email']);
        $password = trim((string) $input['password']);
        $name = trim((string) ($input['name'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ApiResponse::error('Invalid email address', 400);
        }

        $passwordError = PasswordPolicy::validate($password, $email);
        if ($passwordError) {
            return ApiResponse::error($passwordError, 400);
        }

        if (User::where('email', $email)->exists()) {
            return ApiResponse::error('Email already exists', 400);
        }

        $user = User::create([
            'email' => $email,
            'password' => Hash::make($password),
            'name' => $name,
            'points' => 0,
            'last_login_date' => null,
        ]);

        $this->addLoginPoints($user);

        $user->refresh();

        $token = LegacyJwt::generateToken((int) $user->id);

        return ApiResponse::success([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'is_admin' => (bool) ($user->is_admin ?? false),
                'points' => (int) ($user->points ?? 0),
                'last_login_date' => $user->last_login_date?->format('Y-m-d'),
            ],
            'token' => $token,
        ], 'User registered successfully', 201);
    }

    private function addLoginPoints(User $user, int $points = 10): void
    {
        $today = now()->format('Y-m-d');
        $lastLoginDate = $user->last_login_date?->format('Y-m-d');

        if ($lastLoginDate !== $today) {
            $user->points = (int) ($user->points ?? 0) + $points;
            $user->last_login_date = $today;
            $user->save();
        }
    }
}
