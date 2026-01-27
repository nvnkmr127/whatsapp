<?php

namespace App\Services;

use Exception;

class SDPValidator
{
    /**
     * Supported audio codecs
     */
    const SUPPORTED_CODECS = [
        'PCMU',   // G.711 μ-law
        'PCMA',   // G.711 A-law
        'opus',   // Opus codec
        'telephone-event', // DTMF
    ];

    /**
     * Required SDP fields
     */
    const REQUIRED_FIELDS = ['v', 'o', 's', 'c', 't', 'm'];

    /**
     * Validate SDP structure and content
     *
     * @param string $sdp The SDP string to validate
     * @return array ['valid' => bool, 'errors' => array, 'warnings' => array]
     */
    public static function validate(string $sdp): array
    {
        $errors = [];
        $warnings = [];

        // Basic structure validation
        if (empty(trim($sdp))) {
            $errors[] = 'SDP is empty';
            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        // Split into lines
        $lines = preg_split('/\r\n|\r|\n/', trim($sdp));

        if (empty($lines)) {
            $errors[] = 'SDP has no valid lines';
            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        // Check for required fields
        $foundFields = [];
        foreach ($lines as $line) {
            if (preg_match('/^([a-z])=/', $line, $matches)) {
                $foundFields[] = $matches[1];
            }
        }

        foreach (self::REQUIRED_FIELDS as $field) {
            if (!in_array($field, $foundFields)) {
                $errors[] = "Missing required SDP field: {$field}=";
            }
        }

        // Validate version (must be 0)
        $versionLine = self::findLine($lines, 'v');
        if ($versionLine && !preg_match('/^v=0$/', $versionLine)) {
            $errors[] = 'SDP version must be 0';
        }

        // Validate origin line format
        $originLine = self::findLine($lines, 'o');
        if ($originLine && !preg_match('/^o=\S+ \d+ \d+ IN IP[46] \S+$/', $originLine)) {
            $warnings[] = 'Origin line (o=) format may be invalid';
        }

        // Validate connection line format
        $connectionLine = self::findLine($lines, 'c');
        if ($connectionLine && !preg_match('/^c=IN IP[46] \S+$/', $connectionLine)) {
            $errors[] = 'Connection line (c=) format is invalid';
        }

        // Validate media line
        $mediaLine = self::findLine($lines, 'm');
        if ($mediaLine) {
            if (!preg_match('/^m=(audio|video) \d+ \S+ (.+)$/', $mediaLine, $matches)) {
                $errors[] = 'Media line (m=) format is invalid';
            } else {
                // Check if it's audio (WhatsApp primarily uses audio)
                if ($matches[1] !== 'audio') {
                    $warnings[] = "Media type is '{$matches[1]}', expected 'audio'";
                }
            }
        }

        // Validate codecs
        $codecValidation = self::validateCodecs($lines);
        if (!empty($codecValidation['errors'])) {
            $errors = array_merge($errors, $codecValidation['errors']);
        }
        if (!empty($codecValidation['warnings'])) {
            $warnings = array_merge($warnings, $codecValidation['warnings']);
        }

        // Check for security issues
        $securityCheck = self::checkSecurity($sdp);
        if (!empty($securityCheck)) {
            $errors = array_merge($errors, $securityCheck);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Validate codecs in SDP
     *
     * @param array $lines SDP lines
     * @return array ['errors' => array, 'warnings' => array]
     */
    protected static function validateCodecs(array $lines): array
    {
        $errors = [];
        $warnings = [];
        $foundSupportedCodec = false;

        foreach ($lines as $line) {
            if (preg_match('/^a=rtpmap:\d+ ([^\/]+)/', $line, $matches)) {
                $codec = $matches[1];

                if (in_array($codec, self::SUPPORTED_CODECS)) {
                    $foundSupportedCodec = true;
                } else {
                    $warnings[] = "Codec '{$codec}' may not be supported";
                }
            }
        }

        if (!$foundSupportedCodec) {
            $errors[] = 'No supported audio codec found. Supported: ' . implode(', ', self::SUPPORTED_CODECS);
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Check for security issues in SDP
     *
     * @param string $sdp The SDP string
     * @return array Array of security errors
     */
    protected static function checkSecurity(string $sdp): array
    {
        $errors = [];

        // Check for suspicious characters or injection attempts
        if (preg_match('/[<>{}]/', $sdp)) {
            $errors[] = 'SDP contains suspicious characters';
        }

        // Check for excessively long lines (potential DoS)
        $lines = preg_split('/\r\n|\r|\n/', $sdp);
        foreach ($lines as $line) {
            if (strlen($line) > 1000) {
                $errors[] = 'SDP contains excessively long lines';
                break;
            }
        }

        // Check total size (should be reasonable)
        if (strlen($sdp) > 10000) {
            $errors[] = 'SDP is too large (max 10KB)';
        }

        return $errors;
    }

    /**
     * Find a specific line in SDP by field type
     *
     * @param array $lines SDP lines
     * @param string $field Field type (e.g., 'v', 'o', 's')
     * @return string|null The line or null if not found
     */
    protected static function findLine(array $lines, string $field): ?string
    {
        foreach ($lines as $line) {
            if (strpos($line, "{$field}=") === 0) {
                return $line;
            }
        }
        return null;
    }

    /**
     * Sanitize SDP content
     *
     * @param string $sdp The SDP string
     * @return string Sanitized SDP
     */
    public static function sanitize(string $sdp): string
    {
        // Remove any null bytes
        $sdp = str_replace("\0", '', $sdp);

        // Ensure proper line endings (CRLF)
        $sdp = preg_replace('/\r\n|\r|\n/', "\r\n", $sdp);

        // Trim whitespace
        $sdp = trim($sdp);

        return $sdp;
    }

    /**
     * Extract codec information from SDP
     *
     * @param string $sdp The SDP string
     * @return array Array of codec names
     */
    public static function extractCodecs(string $sdp): array
    {
        $codecs = [];
        $lines = preg_split('/\r\n|\r|\n/', $sdp);

        foreach ($lines as $line) {
            if (preg_match('/^a=rtpmap:\d+ ([^\/]+)/', $line, $matches)) {
                $codecs[] = $matches[1];
            }
        }

        return array_unique($codecs);
    }

    /**
     * Validate SDP type (offer or answer)
     *
     * @param string $type The SDP type
     * @return bool
     */
    public static function validateType(string $type): bool
    {
        return in_array($type, ['offer', 'answer']);
    }

    /**
     * Get a human-readable validation summary
     *
     * @param array $validation Validation result from validate()
     * @return string
     */
    public static function getValidationSummary(array $validation): string
    {
        if ($validation['valid']) {
            $summary = 'SDP is valid';
            if (!empty($validation['warnings'])) {
                $summary .= ' (with warnings)';
            }
        } else {
            $summary = 'SDP validation failed: ' . implode('; ', $validation['errors']);
        }

        return $summary;
    }
}
