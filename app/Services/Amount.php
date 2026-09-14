<?php
declare(strict_types=1);
namespace App\Services;

/** Decimal amounts are compared in integer minor units, never floating point. */
final class Amount
{
    public const MAX_CENTS = 999999999999999;

    public static function cents(mixed $value, string $field = 'amount', bool $allowZero = false): int
    {
        $text = is_scalar($value) ? trim((string)$value) : '';
        if (!preg_match('/^\d{1,13}(?:\.\d{1,2})?$/D', $text)) {
            throw new FieldValidationException($field, 'Enter a non-negative amount with at most 2 decimal places.');
        }
        $parts = explode('.', $text);
        $cents = (int)$parts[0] * 100 + (int)str_pad($parts[1] ?? '', 2, '0');
        if (!$allowZero && $cents === 0) {
            throw new FieldValidationException($field, 'Enter an amount greater than zero.');
        }
        return $cents;
    }

    public static function storedCents(string $value): int
    {
        return str_starts_with($value, '-') ? -self::cents(substr($value, 1), 'amount', true) : self::cents($value, 'amount', true);
    }

    public static function decimal(int $cents): string
    {
        return ($cents < 0 ? '-' : '').intdiv(abs($cents), 100).'.'.str_pad((string)(abs($cents) % 100), 2, '0', STR_PAD_LEFT);
    }
}
