<?php

namespace App\Validators;

use App\DTOs\ValidationError;
use App\DTOs\ValidationResult;
use App\Models\WhatsappTemplate;

class TemplateValidator
{
    const MARKETING_KEYWORDS = [
        'offer',
        'discount',
        'free',
        'sale',
        'deal',
        'limited time',
        'buy one',
        'promo',
        'coupon',
        'exclusive'
    ];

    /**
     * Validate template and return a comprehensive readiness profile
     */
    public function validate(WhatsappTemplate $template, array $runtimeParams = []): ValidationResult
    {
        $result = new ValidationResult();
        $errors = [];
        $score = 100;

        // 1. Lifecycle Check
        if ($template->status !== 'APPROVED') {
            $score -= 50;
            $errors[] = [
                'code' => 'STATUS_INELIGIBLE',
                'description' => "Template status is {$template->status}, not APPROVED",
                'severity' => 'error'
            ];
        }

        if ($template->is_paused) {
            $score -= 30;
            $errors[] = [
                'code' => 'STATUS_PAUSED',
                'description' => "Template is currently PAUSED by Meta",
                'severity' => 'error'
            ];
        }

        // 2. Structural Integrity
        $components = $template->components ?? [];
        $category = $template->category;

        // 3. Compliance Guardrails (NEW)
        if ($category === 'UTILITY') {
            $qualityWarnings = $this->validateUtilityCompliance($components);
            foreach ($qualityWarnings as $warn) {
                $score -= 20;
                $errors[] = $warn;
            }
        }

        foreach ($components as $component) {
            // Category-specific structural rules (UC-06)
            if ($category === 'AUTHENTICATION') {
                if ($component['type'] === 'HEADER' && in_array($component['format'] ?? '', ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
                    $score -= 100; // Fatal misuse
                    $errors[] = [
                        'code' => 'CAT_AUTH_MEDIA_DISALLOWED',
                        'description' => "Authentication templates cannot contain media headers",
                        'severity' => 'error'
                    ];
                }

                if ($component['type'] === 'BUTTONS' && isset($component['buttons'])) {
                    foreach ($component['buttons'] as $btn) {
                        if (!in_array($btn['type'] ?? '', ['OTP', 'COPY_CODE'])) {
                            $score -= 100;
                            $errors[] = [
                                'code' => 'CAT_AUTH_BUTTON_INVALID',
                                'description' => "Authentication templates only allow OTP or COPY_CODE buttons",
                                'severity' => 'error'
                            ];
                        }
                    }
                }
            }

            if ($component['type'] === 'BODY' && isset($component['text'])) {
                if (!$this->validateVariablesSequential($component['text'])) {
                    $score -= 40;
                    $errors[] = [
                        'code' => 'VARIABLE_SKEW',
                        'description' => "Body placeholders must be sequential {{1}}, {{2}}...",
                        'severity' => 'error'
                    ];
                }

                // Generic Content / Ambiguity Check
                if ($this->isContentTooGeneric($component['text'])) {
                    $score -= 30;
                    $errors[] = [
                        'code' => 'CONTENT_TOO_GENERIC',
                        'description' => "Template body is too generic. Add more context around variables to avoid rejection.",
                        'severity' => 'warning'
                    ];
                }
            }

            if ($component['type'] === 'HEADER' && in_array($component['format'] ?? '', ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
                if (empty($runtimeParams['header_media_url'])) {
                    $score -= 10; // Potentially unbound if no params provided
                    $errors[] = [
                        'code' => 'MEDIA_UNBOUND',
                        'description' => "Media header requires a file handle/URL at runtime",
                        'severity' => 'warning'
                    ];
                }
            }

            if ($component['type'] === 'BUTTONS' && isset($component['buttons'])) {
                foreach ($component['buttons'] as $btn) {
                    if (($btn['type'] ?? '') === 'URL' && isset($btn['url']) && str_contains($btn['url'], '{{')) {
                        if (!str_contains($btn['url'], '{{1}}')) {
                            $score -= 20;
                            $errors[] = [
                                'code' => 'BUTTON_VARIABLE_INVALID',
                                'description' => "Dynamic buttons must use {{1}} suffix",
                                'severity' => 'error'
                            ];
                        }
                    }

                    // CTA Safety Check (Utility shouldn't have shop links ideally)
                    if ($category === 'UTILITY' && ($btn['type'] ?? '') === 'URL') {
                        $url = strtolower($btn['url'] ?? '');
                        if (str_contains($url, 'shop') || str_contains($url, 'buy') || str_contains($url, 'store')) {
                            $score -= 10;
                            $errors[] = [
                                'code' => 'CTA_MISMATCH_RISK',
                                'description' => "Utility template contains 'Shopping' related URL. May be re-categorized as Marketing.",
                                'severity' => 'warning'
                            ];
                        }
                    }

                    // FLOW INTEGRITY CHECK
                    if (($btn['type'] ?? '') === 'FLOW') {
                        $flowId = $btn['flow_id'] ?? null;
                        if ($flowId) {
                            $flow = \App\Models\WhatsAppFlow::where('flow_id', $flowId)->first();
                            if (!$flow) {
                                $score -= 100;
                                $errors[] = [
                                    'code' => 'FLOW_ORPHANED',
                                    'description' => "Linked Flow ID {$flowId} not found in system.",
                                    'severity' => 'error'
                                ];
                            } else {
                                // Check Flow Readiness
                                $fValidator = new \App\Validators\FlowReadinessValidator();
                                $fResult = $fValidator->validate($flow);
                                if (!$fResult->isValid()) {
                                    $score -= 50;
                                    $reason = $fResult->getBlockingReason();
                                    $errors[] = [
                                        'code' => 'FLOW_NOT_READY',
                                        'description' => "Linked Flow is not ready: {$reason}",
                                        'severity' => 'error'
                                    ];
                                }

                                // Check Entry Point
                                $epValidator = new \App\Validators\FlowEntryPointValidator();
                                $epResult = $epValidator->validate($flow, 'template');
                                if (!$epResult->isValid()) {
                                    $score -= 50;
                                    $reason = $epResult->getBlockingReason();
                                    $errors[] = [
                                        'code' => 'FLOW_ENTRY_BLOCKED',
                                        'description' => "Flow cannot be used in Templates: {$reason}",
                                        'severity' => 'error'
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        $template->update([
            'readiness_score' => max(0, $score),
            'validation_results' => $errors
        ]);

        foreach ($errors as $err) {
            $result->addError(new ValidationError(
                code: $err['code'],
                message: $err['description'],
                severity: $err['severity']
            ));
        }

        return $result;
    }

    protected function validateUtilityCompliance(array $components): array
    {
        $warnings = [];
        foreach ($components as $component) {
            $text = '';
            if (isset($component['text']))
                $text .= $component['text'];

            // Check headers too

            if ($text) {
                foreach (self::MARKETING_KEYWORDS as $keyword) {
                    if (stripos($text, $keyword) !== false) {
                        $warnings[] = [
                            'code' => 'CAT_UTILITY_MARKETING_DETECTED',
                            'description' => "Utility template contains verification keyword '{$keyword}'. High risk of rejection or re-categorization.",
                            'severity' => 'error' // Treat as error for strict compliance
                        ];
                        // Break after first match to avoid noise
                        break;
                    }
                }
            }
        }
        return $warnings;
    }

    protected function isContentTooGeneric(string $text): bool
    {
        // Remove variables {{n}}
        $clean = preg_replace('/\{\{\d+\}\}/', '', $text);
        // Remove whitespace
        $clean = trim($clean);

        // If remaining text is very short (e.g. just punctuation or "Hi"), it's risky
        // Threshold: 10 chars is a safe bet for "Context".
        // "Hi {{1}}, here is your code {{2}}" -> "Hi , here is your code " (Length ~20. OK)
        // "Hi {{1}}" -> "Hi " (Length 3. FAIL)
        return strlen($clean) < 10;
    }

    protected function validateVariablesSequential(string $text): bool
    {
        if (preg_match_all('/\{\{(\d+)\}\}/', $text, $matches)) {
            $indices = $matches[1];
            // Meta requires first to be {{1}}, second {{2}}...
            foreach ($indices as $i => $value) {
                if ((int) $value !== ($i + 1)) {
                    return false;
                }
            }
        }
        return true;
    }
}
