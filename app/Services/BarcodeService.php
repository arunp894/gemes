<?php

namespace App\Services;

use App\Models\Barcode;

/**
 * BarcodeService
 *
 * Centralised logic for barcode generation, format validation, and
 * platform-wide uniqueness checks. Used by both FormRequests (validation)
 * and Controllers (auto-generation + AJAX live validation).
 *
 * Format rules summary:
 *   - EAN-13   : 13 digits, last is check digit (mod-10, alternating 1/3 weights)
 *   - EAN-8    :  8 digits, last is check digit (mod-10, alternating 3/1 weights)
 *   - UPC-A    : 12 digits, last is check digit (mod-10, alternating 3/1 weights)
 *   - Code 128 : alphanumeric, 1-48 chars (practical upper bound)
 *   - QR Code  : free-form 1-300 chars
 *   - Custom   : free-form 1-100 chars
 */
class BarcodeService
{
    /**
     * Generate a unique EAN-13 barcode value.
     * Retries with new randoms until DB uniqueness is satisfied.
     */
    public function generateUniqueEan13(int $maxAttempts = 20): string
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            // Use a sensible internal prefix (200-299 are unrestricted
            // / internal-use ranges per GS1) so generated codes don't
            // collide with real-world product prefixes.
            $base = '200' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
            $candidate = $base . $this->ean13CheckDigit($base);

            if (! $this->existsInDatabase($candidate)) {
                return $candidate;
            }
        }

        // Extremely unlikely — surface as a runtime error so it doesn't
        // silently fall through to a duplicate-key DB exception.
        throw new \RuntimeException('Unable to generate a unique EAN-13 after ' . $maxAttempts . ' attempts.');
    }

    /**
     * Validate any supported barcode format.
     * Returns null on success, or a human-readable error string on failure.
     */
    public function validateFormat(string $value, string $format): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return 'Barcode value is required.';
        }

        return match ($format) {
            Barcode::FORMAT_EAN_13   => $this->validateEan13($value),
            Barcode::FORMAT_EAN_8    => $this->validateEan8($value),
            Barcode::FORMAT_UPC_A    => $this->validateUpcA($value),
            Barcode::FORMAT_CODE_128 => $this->validateCode128($value),
            Barcode::FORMAT_QR_CODE  => $this->validateQrCode($value),
            Barcode::FORMAT_CUSTOM   => $this->validateCustom($value),
            default                  => 'Unsupported barcode format.',
        };
    }

    /**
     * Validate an EAN-13: must be 13 digits with a correct check digit.
     */
    public function validateEan13(string $value): ?string
    {
        if (! preg_match('/^\d{13}$/', $value)) {
            return 'EAN-13 must be exactly 13 digits.';
        }

        $base = substr($value, 0, 12);
        $check = (int) substr($value, 12, 1);

        if ($this->ean13CheckDigit($base) !== (string) $check) {
            return 'Invalid EAN-13 barcode — check digit mismatch.';
        }

        return null;
    }

    /**
     * Validate an EAN-8: must be 8 digits with a correct check digit.
     */
    public function validateEan8(string $value): ?string
    {
        if (! preg_match('/^\d{8}$/', $value)) {
            return 'EAN-8 must be exactly 8 digits.';
        }

        $base = substr($value, 0, 7);
        $check = (int) substr($value, 7, 1);

        if ($this->ean8CheckDigit($base) !== (string) $check) {
            return 'Invalid EAN-8 barcode — check digit mismatch.';
        }

        return null;
    }

    /**
     * Validate a UPC-A: must be 12 digits with a correct check digit.
     */
    public function validateUpcA(string $value): ?string
    {
        if (! preg_match('/^\d{12}$/', $value)) {
            return 'UPC-A must be exactly 12 digits.';
        }

        $base = substr($value, 0, 11);
        $check = (int) substr($value, 11, 1);

        if ($this->upcaCheckDigit($base) !== (string) $check) {
            return 'Invalid UPC-A barcode — check digit mismatch.';
        }

        return null;
    }

    /**
     * Validate Code 128: printable ASCII, 1-48 chars (practical maximum).
     */
    public function validateCode128(string $value): ?string
    {
        if (mb_strlen($value) > 48) {
            return 'Code 128 value is too long (max 48 characters).';
        }

        // Allow only printable ASCII (Code 128 supports all ASCII 0-127
        // but in practice we want printable for label readability).
        if (! preg_match('/^[\x20-\x7E]+$/', $value)) {
            return 'Code 128 may only contain printable ASCII characters.';
        }

        return null;
    }

    /**
     * Validate QR Code: free-form text, 1-300 chars.
     */
    public function validateQrCode(string $value): ?string
    {
        if (mb_strlen($value) > 300) {
            return 'QR Code value is too long (max 300 characters).';
        }

        return null;
    }

    /**
     * Validate Custom / Internal: free-form, 1-100 chars.
     */
    public function validateCustom(string $value): ?string
    {
        if (mb_strlen($value) > 100) {
            return 'Custom barcode value is too long (max 100 characters).';
        }

        return null;
    }

    /**
     * Check if a barcode value already exists in the DB.
     * Includes soft-deleted rows (barcodes are platform-wide-unique forever).
     */
    public function existsInDatabase(string $value, ?int $ignoreId = null): bool
    {
        $query = Barcode::withTrashed()->where('barcode_value', $value);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /* -----------------------------------------------------------------
     |  Check digit calculators
     | -----------------------------------------------------------------
     */

    /**
     * EAN-13 check digit. Input: first 12 digits. Output: single-digit string.
     * Algorithm: sum odd-positioned digits ×1 + even-positioned ×3, then
     *   check = (10 - (sum mod 10)) mod 10.
     */
    public function ean13CheckDigit(string $first12): string
    {
        if (! preg_match('/^\d{12}$/', $first12)) {
            throw new \InvalidArgumentException('ean13CheckDigit requires exactly 12 digits.');
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $first12[$i];
            // Position 1 (index 0) = ×1, position 2 (index 1) = ×3, alternating.
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }

        return (string) ((10 - ($sum % 10)) % 10);
    }

    /**
     * EAN-8 check digit. Input: first 7 digits.
     * Algorithm: same as EAN-13 but weights start with ×3 (because the
     * length is odd, so the right-aligned alternation starts differently).
     */
    public function ean8CheckDigit(string $first7): string
    {
        if (! preg_match('/^\d{7}$/', $first7)) {
            throw new \InvalidArgumentException('ean8CheckDigit requires exactly 7 digits.');
        }

        $sum = 0;
        for ($i = 0; $i < 7; $i++) {
            $digit = (int) $first7[$i];
            // EAN-8: position 1 (index 0) = ×3, position 2 = ×1, alternating.
            $sum += ($i % 2 === 0) ? $digit * 3 : $digit;
        }

        return (string) ((10 - ($sum % 10)) % 10);
    }

    /**
     * UPC-A check digit. Input: first 11 digits.
     * Algorithm: odd-positioned (1st, 3rd, ...) ×3, even-positioned ×1.
     */
    public function upcaCheckDigit(string $first11): string
    {
        if (! preg_match('/^\d{11}$/', $first11)) {
            throw new \InvalidArgumentException('upcaCheckDigit requires exactly 11 digits.');
        }

        $sum = 0;
        for ($i = 0; $i < 11; $i++) {
            $digit = (int) $first11[$i];
            // UPC-A: position 1 (index 0) = ×3, position 2 = ×1, alternating.
            $sum += ($i % 2 === 0) ? $digit * 3 : $digit;
        }

        return (string) ((10 - ($sum % 10)) % 10);
    }
}
