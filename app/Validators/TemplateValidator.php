<?php

namespace App\Validators;

use App\DTOs\ValidationError;
use App\DTOs\ValidationResult;
use App\Models\WhatsappTemplate;

class TemplateValidator
{
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
                'severity' => 'fatal'
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

        foreach ($components as $component) {
            // Category-specific structural rules (UC-06)
            if ($category === 'AUTHENTICATION') {
                if ($component['type'] === 'HEADER' && in_array($component['format'] ?? '', ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
                    $score -= 100; // Fatal misuse
                    $errors[] = [
                        'code' => 'CAT_AUTH_MEDIA_DISALLOWED',
                        'description' => "Authentication templates cannot contain media headers",
                        'severity' => 'fatal'
                    ];
                }

                if ($component['type'] === 'BUTTONS' && isset($component['buttons'])) {
                    foreach ($component['buttons'] as $btn) {
                        if (!in_array($btn['type'] ?? '', ['OTP', 'COPY_CODE'])) {
                            $score -= 100;
                            $errors[] = [
                                'code' => 'CAT_AUTH_BUTTON_INVALID',
                                'description' => "Authentication templates only allow OTP or COPY_CODE buttons",
                                'severity' => 'fatal'
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
                        'severity' => 'fatal'
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
