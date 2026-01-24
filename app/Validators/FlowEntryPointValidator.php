<?php

namespace App\Validators;

use App\DTOs\ValidationError;
use App\DTOs\ValidationResult;
use App\Models\WhatsAppFlow;

class FlowEntryPointValidator
{
    /**
     * Validate Flow Usage against allowed entry points.
     * 
     * @param WhatsAppFlow $flow
     * @param string $entryPointType 'template', 'interactive', 'direct'
     * @return ValidationResult
     */
    public function validate(WhatsAppFlow $flow, string $entryPointType): ValidationResult
    {
        $result = new ValidationResult();
        $config = $flow->entry_point_config ?? [];

        // If config is empty, assume permissive default OR strict default
        // Let's assume permissive (any entry allowed) unless restricted.
        $allowed = $config['allowed_entry_points'] ?? ['template', 'interactive', 'direct'];

        if (!in_array($entryPointType, $allowed)) {
            $result->addError(new ValidationError(
                code: 'ENTRY_POINT_DISALLOWED',
                message: "This Flow is not configured to be triggered via '{$entryPointType}'. Allowed: " . implode(', ', $allowed),
                severity: 'error'
            ));
        }

        return $result;
    }
}
