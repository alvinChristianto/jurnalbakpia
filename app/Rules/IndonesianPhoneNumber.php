<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Mirrors FE-bakpia/lib/phone-validation.ts: must start with +62 or 08 and
 * contain 11-13 digits total (excluding the leading "+"). Keeps backend and
 * frontend phone validation in lockstep.
 */
class IndonesianPhoneNumber implements ValidationRule
{
    private const MIN_DIGITS = 11;

    private const MAX_DIGITS = 13;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Nomor telepon tidak valid.');

            return;
        }

        $trimmed = trim($value);

        if (preg_match('/[a-zA-Z]/', $trimmed)) {
            $fail('Nomor telepon tidak boleh mengandung huruf.');

            return;
        }

        $stripped = preg_replace('/[-\s]/', '', $trimmed);

        if (! preg_match('/^\+?[0-9]+$/', $stripped)) {
            $fail('Nomor telepon hanya boleh berisi angka.');

            return;
        }

        if (! preg_match('/^(\+62|08)/', $stripped)) {
            $fail('Nomor telepon harus diawali dengan +62 atau 08.');

            return;
        }

        $digitCount = strlen(preg_replace('/\D/', '', $stripped));

        if ($digitCount > self::MAX_DIGITS) {
            $fail('Nomor telepon terlalu panjang (maksimal '.self::MAX_DIGITS.' digit).');

            return;
        }

        if ($digitCount < self::MIN_DIGITS) {
            $fail('Nomor telepon terlalu pendek (minimal '.self::MIN_DIGITS.' digit).');
        }
    }
}
