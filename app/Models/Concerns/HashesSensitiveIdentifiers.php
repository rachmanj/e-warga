<?php

namespace App\Models\Concerns;

trait HashesSensitiveIdentifiers
{
    protected static function identifierHmac(string $value): string
    {
        return hash_hmac('sha256', $value, config('app.key'));
    }

    protected static function maskSixteenDigitIdentifier(?string $digits): ?string
    {
        if ($digits === null || strlen($digits) !== 16) {
            return null;
        }

        return substr($digits, 0, 4).str_repeat('.', 8).substr($digits, -4);
    }
}
