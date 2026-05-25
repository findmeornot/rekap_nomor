<?php

namespace App\Support;

final class PhoneNumber
{
    public static function normalize(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        if (! str_starts_with($digits, '62')) {
            return null;
        }

        return $digits;
    }

    public static function isValid(string $raw): bool
    {
        $normalized = self::normalize($raw);

        if ($normalized === null) {
            return false;
        }

        $length = strlen($normalized);

        return $length >= 10 && $length <= 15;
    }

    public static function validationMessage(): string
    {
        return 'Nomor tidak valid. Gunakan format 08, 62, atau +62 (10-15 digit).';
    }
}
