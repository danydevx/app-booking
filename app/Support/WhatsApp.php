<?php

namespace App\Support;

final class WhatsApp
{
    /**
     * Build a click-to-chat URL from a phone number and optional message.
     */
    public static function url(?string $phone, ?string $message = null): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return null;
        }

        $url = 'https://wa.me/'.$digits;
        $message = trim((string) $message);

        return $message === '' ? $url : $url.'?text='.rawurlencode($message);
    }
}
