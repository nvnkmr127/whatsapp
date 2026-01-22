<?php

namespace App\Helpers;

use InvalidArgumentException;

class PhoneNumberHelper
{
    /**
     * Normalize phone number to E.164 format.
     * 
     * @param string $phone Raw phone number input
     * @return string Normalized phone number with + prefix
     * @throws InvalidArgumentException If phone number is invalid
     */
    public static function normalize(string $phone): string
    {
        // Remove all whitespace and special characters except + and digits
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // Handle empty input
        if (empty($phone)) {
            throw new InvalidArgumentException("Phone number cannot be empty");
        }

        // If already has + prefix, validate and return
        if (str_starts_with($phone, '+')) {
            return self::validate($phone);
        }

        // Add default country code if missing
        $defaultCode = config('app.default_country_code', '+91');

        // Remove leading zeros (common in local formats)
        $phone = ltrim($phone, '0');

        // Check if it already starts with the numeric country code (e.g. 91...)
        $numericCode = ltrim($defaultCode, '+');
        if (str_starts_with($phone, $numericCode) && strlen($phone) > strlen($numericCode) + 8) {
            // Assume it already has the code, just add +
            return self::validate('+' . $phone);
        }

        // Construct E.164 format
        $normalized = $defaultCode . $phone;

        return self::validate($normalized);
    }

    /**
     * Validate phone number format.
     * 
     * @param string $phone Phone number to validate
     * @return string Validated phone number
     * @throws InvalidArgumentException If format is invalid
     */
    protected static function validate(string $phone): string
    {
        // E.164 format: + followed by 10-15 digits
        if (!preg_match('/^\+\d{10,15}$/', $phone)) {
            throw new InvalidArgumentException(
                "Invalid phone number format: {$phone}. Expected E.164 format (+[country code][number])"
            );
        }

        return $phone;
    }

    /**
     * Check if two phone numbers are equivalent.
     * 
     * @param string $phone1
     * @param string $phone2
     * @return bool
     */
    public static function areEqual(string $phone1, string $phone2): bool
    {
        try {
            return self::normalize($phone1) === self::normalize($phone2);
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Format phone number for display.
     * 
     * @param string $phone E.164 formatted phone number
     * @param string $format Display format (e.g., 'international', 'national')
     * @return string Formatted phone number
     */
    public static function format(string $phone, string $format = 'international'): string
    {
        $normalized = self::normalize($phone);

        if ($format === 'international') {
            return $normalized;
        }

        // For national format, remove country code
        // This is a simple implementation - consider using libphonenumber for production
        if ($format === 'national') {
            $defaultCode = config('app.default_country_code', '+91');
            if (str_starts_with($normalized, $defaultCode)) {
                return substr($normalized, strlen($defaultCode));
            }
        }

        return $normalized;
    }
}
