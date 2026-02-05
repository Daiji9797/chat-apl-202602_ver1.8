<?php

namespace App\Support;

class LegacyJwt
{
    public static function generateToken(int $userId): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60),
            'userId' => $userId,
        ]);

        $base64Header = self::base64urlEncode($header);
        $base64Payload = self::base64urlEncode($payload);
        $signature = self::base64urlEncode(
            hash_hmac('sha256', $base64Header . '.' . $base64Payload, (string) env('SESSION_SECRET', 'default_secret_key'), true)
        );

        return $base64Header . '.' . $base64Payload . '.' . $signature;
    }

    public static function verifyToken(string $token): array|false
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return false;
        }

        [$header, $payload, $signature] = $parts;

        $expectedSignature = self::base64urlEncode(
            hash_hmac('sha256', $header . '.' . $payload, (string) env('SESSION_SECRET', 'default_secret_key'), true)
        );

        if (!hash_equals($signature, $expectedSignature)) {
            return false;
        }

        $decodedPayload = json_decode(self::base64urlDecode($payload), true);

        if (!$decodedPayload) {
            return false;
        }

        if (isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
            return false;
        }

        return $decodedPayload;
    }

    private static function base64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64urlDecode(string $data): string
    {
        $data = strtr($data, '-_', '+/');
        return base64_decode($data . str_repeat('=', 4 - strlen($data) % 4));
    }
}
