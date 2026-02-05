<?php

namespace App\Support;

use Illuminate\Support\Str;

class PasswordPolicy
{
    public static function validate(string $password, ?string $email = null): ?string
    {
        if (Str::length($password) < 8) {
            return 'Password must be at least 8 characters';
        }

        if (!preg_match('/[a-z]/', $password)) {
            return 'Password must contain at least one lowercase letter';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return 'Password must contain at least one uppercase letter';
        }

        if (!preg_match('/[0-9]/', $password)) {
            return 'Password must contain at least one number';
        }

        if (self::hasRepeatedChars($password, 3)) {
            return 'Password cannot contain the same character repeated 3 or more times in a row';
        }

        if (self::hasSequentialNumbers($password, 3)) {
            return 'Password cannot contain 3 or more consecutive numbers in ascending or descending order';
        }

        if (self::hasKeyboardSequence($password, 3)) {
            return 'Password cannot contain 3 or more consecutive keyboard-adjacent characters';
        }

        if ($email !== null) {
            $emailParts = explode('@', strtolower($email));
            $passwordLower = strtolower($password);
            if ($passwordLower === strtolower($email) || str_contains($passwordLower, $emailParts[0])) {
                return 'Password cannot be the same as or contain your email address';
            }
        }

        return null;
    }

    private static function hasSequentialNumbers(string $password, int $minLen = 3): bool
    {
        $len = strlen($password);
        $run = 1;
        for ($i = 1; $i < $len; $i++) {
            if (ctype_digit($password[$i - 1]) && ctype_digit($password[$i])) {
                $diff = intval($password[$i]) - intval($password[$i - 1]);
                if ($diff === 1 || $diff === -1) {
                    $run++;
                    if ($run >= $minLen) {
                        return true;
                    }
                    continue;
                }
            }
            $run = 1;
        }
        return false;
    }

    private static function hasRepeatedChars(string $password, int $minLen = 3): bool
    {
        $len = strlen($password);
        $run = 1;
        for ($i = 1; $i < $len; $i++) {
            if (strcasecmp($password[$i], $password[$i - 1]) === 0) {
                $run++;
                if ($run >= $minLen) {
                    return true;
                }
            } else {
                $run = 1;
            }
        }
        return false;
    }

    private static function hasKeyboardSequence(string $password, int $minLen = 3): bool
    {
        $rows = ['qwertyuiop', 'asdfghjkl', 'zxcvbnm', '1234567890'];
        $lower = strtolower($password);
        foreach ($rows as $row) {
            $rev = strrev($row);
            $rowLen = strlen($row);
            for ($i = 0; $i <= $rowLen - $minLen; $i++) {
                $chunk = substr($row, $i, $minLen);
                if (str_contains($lower, $chunk) || str_contains($lower, substr($rev, $i, $minLen))) {
                    return true;
                }
            }
        }
        return false;
    }
}
