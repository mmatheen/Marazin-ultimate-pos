<?php

namespace App\Helpers;

class SmsHelper
{
    public static function formatPhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '94') && strlen($digits) === 11) {
            return '+' . $digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '+94' . substr($digits, 1);
        }

        if (str_starts_with($digits, '7') && strlen($digits) === 9) {
            return '+94' . $digits;
        }

        if (str_starts_with($digits, '947') && strlen($digits) === 12) {
            return '+' . $digits;
        }

        if (str_starts_with($digits, '9') && strlen($digits) === 11) {
            return '+' . $digits;
        }

        return '+' . $digits;
    }
}
