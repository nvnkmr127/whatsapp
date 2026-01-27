<?php

namespace App\Services;

use Illuminate\Support\Arr;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;

class WebhookMappingService
{
    /**
     * Map fields from webhook payload to internal structure
     */
    public function mapFields(array $payload, array $mappingRules): array
    {
        $mappedData = [];

        foreach ($mappingRules as $internalField => $externalPath) {
            // Check for direct static value (prefixed with "STATIC:")
            if (str_starts_with($externalPath, 'STATIC:')) {
                $mappedData[$internalField] = substr($externalPath, 7);
                continue;
            }

            $value = $this->extractNestedValue($payload, $externalPath);

            if ($value !== null) {
                $mappedData[$internalField] = $value;
            }
        }

        return $mappedData;
    }

    /**
     * Apply transformations to mapped data
     */
    public function transformData(array $data, array $transformationRules): array
    {
        $transformed = $data;

        foreach ($transformationRules as $field => $transformer) {
            if (isset($transformed[$field])) {
                $transformed[$field] = $this->applyTransformation($transformed[$field], $transformer);
            }
        }

        return $transformed;
    }

    /**
     * Extract nested value using dot notation
     */
    public function extractNestedValue(array $array, string $path)
    {
        return Arr::get($array, $path);
    }

    /**
     * Apply a specific transformation to a value
     */
    protected function applyTransformation($value, string $transformer)
    {
        return match ($transformer) {
            'format_phone' => $this->formatPhoneNumber($value),
            'to_float' => $this->toFloat($value),
            'to_int' => $this->toInt($value),
            'parse_date' => $this->parseDate($value),
            'ucwords' => ucwords($value),
            'lowercase' => strtolower($value),
            'uppercase' => strtoupper($value),
            'trim' => trim($value),
            'json_encode' => json_encode($value),
            'json_decode' => json_decode($value, true),
            'stripe_amount_to_decimal' => $this->stripeAmountToDecimal($value),
            default => $value,
        };
    }

    /**
     * Format phone number to E.164 format
     */
    protected function formatPhoneNumber($phone)
    {
        if (empty($phone)) {
            return null;
        }

        try {
            $phoneUtil = PhoneNumberUtil::getInstance();

            // Try to parse with default region (you can make this configurable)
            $phoneNumber = $phoneUtil->parse($phone, 'US');

            if ($phoneUtil->isValidNumber($phoneNumber)) {
                return $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);
            }
        } catch (\Exception $e) {
            // If parsing fails, return original
            return $phone;
        }

        return $phone;
    }

    /**
     * Convert value to float
     */
    protected function toFloat($value)
    {
        return (float) $value;
    }

    /**
     * Convert value to integer
     */
    protected function toInt($value)
    {
        return (int) $value;
    }

    /**
     * Parse date string to timestamp
     */
    protected function parseDate($value)
    {
        try {
            return \Carbon\Carbon::parse($value)->toDateTimeString();
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Convert Stripe amount (cents) to decimal
     */
    protected function stripeAmountToDecimal($amount)
    {
        return $amount / 100;
    }

    /**
     * Extract event type from payload
     */
    public function extractEventType(array $payload, ?string $eventTypePath = null): ?string
    {
        if (!$eventTypePath) {
            return $payload['event'] ?? $payload['type'] ?? $payload['topic'] ?? null;
        }

        return $this->extractNestedValue($payload, $eventTypePath);
    }

    /**
     * Extract unique external ID from payload or headers
     */
    public function extractExternalId(array $payload, array $headers, ?string $platform = null): ?string
    {
        // Platform specific common locations
        if ($platform === 'shopify') {
            return $headers['x-shopify-webhook-id'][0] ?? null;
        }

        if ($platform === 'woocommerce') {
            return $headers['x-wc-webhook-id'][0] ?? null;
        }

        if ($platform === 'stripe') {
            return $payload['id'] ?? null;
        }

        // Generic fallback
        return $payload['id'] ?? $payload['uuid'] ?? $payload['guid'] ?? null;
    }

    /**
     * Generate a unique hash for the payload content
     */
    public function generateDeduplicationHash(array $payload): string
    {
        return md5(json_encode($payload));
    }

    /**
     * Validate mapped data
     */
    public function validateMappedData(array $data, array $rules = []): bool
    {
        // Basic validation - ensure phone number exists
        if (empty($data['phone_number'])) {
            return false;
        }

        // Additional custom validation rules can be added here
        foreach ($rules as $field => $rule) {
            if (!isset($data[$field]) && $rule === 'required') {
                return false;
            }
        }

        return true;
    }
}
