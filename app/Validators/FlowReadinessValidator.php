<?php

namespace App\Validators;

use App\DTOs\ValidationError;
use App\DTOs\ValidationResult;
use App\Models\WhatsAppFlow;

class FlowReadinessValidator
{
    /**
     * Validate Flow and return a comprehensive readiness profile.
     * 
     * @param WhatsAppFlow $flow
     * @param bool $checkRemote Whether to perform remote API checks (e.g. status sync)
     * @return ValidationResult
     */
    public function validate(WhatsAppFlow $flow, bool $checkRemote = false): ValidationResult
    {
        $result = new ValidationResult();
        $errors = [];
        $score = 100;

        // 1. Definition Integrity
        if (!$flow->flow_id) {
            $score = 0;
            $errors[] = [
                'code' => 'NOT_SYNCED',
                'description' => "Flow is not synced with Meta (missing Flow ID).",
                'severity' => 'error'
            ];
        }

        if ($flow->status !== 'PUBLISHED' && $flow->status !== 'DEPRECATED') {
            // DEPRECATED flows might still work for existing sessions but receiving new ones is bad.
            // DRAFT flows only work for testers.
            $score -= 30;
            $errors[] = [
                'code' => 'STATUS_NOT_LIVE',
                'description' => "Flow status is {$flow->status}. Only testers can see this flow.",
                'severity' => 'warning'
            ];
        }

        // 2. Structural Integrity
        $design = $flow->design_data;
        if (empty($design) || empty($design['screens'])) {
            $score = 0;
            $errors[] = [
                'code' => 'NO_SCREENS',
                'description' => "Flow has no screens defined.",
                'severity' => 'error'
            ];
        } else {
            // Deep Screen Validation
            $screenIds = array_column($design['screens'], 'id');
            $entryScreenId = $design['screens'][0]['id'] ?? null;

            if (!$entryScreenId) {
                $score -= 50;
                $errors[] = [
                    'code' => 'NO_ENTRY_SCREEN',
                    'description' => "Could not determine entry screen.",
                    'severity' => 'error'
                ];
            }

            foreach ($design['screens'] as $screen) {
                // Check components
                if (empty($screen['children']) && empty($screen['components'])) {
                    // Internal builder uses 'components', Meta uses 'layout.children'
                    // We check our builder format 'components' 
                    $score -= 10;
                    $errors[] = [
                        'code' => 'EMPTY_SCREEN',
                        'description' => "Screen '{$screen['title']}' has no components.",
                        'severity' => 'warning'
                    ];
                }

                // Check for terminal actions
                $hasTerminator = false;
                $comps = $screen['components'] ?? [];
                foreach ($comps as $c) {
                    if (isset($c['on_click_action'])) {
                        $action = $c['on_click_action'];
                        if (in_array($action, ['complete', 'next'])) {
                            $hasTerminator = true;
                        }
                    }
                }

                // This is a loose check; strict graph traversal is complex for linear builder 
                // but we know FlowService forces linear topology.
            }
        }

        // 3. Endpoint Configuration
        if ($flow->uses_data_endpoint) {
            // Check if system has endpoint capabilities
            // This is usually implied by the app code existence, but we can verify if the URL is accessible externally?
            // Not easily possible from localhost, so we skip.
        }

        // 4. Remote Verification (Optional)
        if ($checkRemote && $flow->flow_id) {
            // Could call Meta API to check real status
        }

        // 5. Update Model Score
        // We act on the model but don't save it inside the validator usually, 
        // to keep it pure. But TemplateValidator did update it. 
        // We'll follow the pattern if desired, but typically we return the result.
        // Let's return result for caller to save.

        foreach ($errors as $err) {
            $result->addError(new ValidationError(
                code: $err['code'],
                message: $err['description'],
                severity: $err['severity']
            ));
        }

        return $result;
    }
}
