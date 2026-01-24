<?php

namespace App\Validators;

use App\Models\WhatsAppFlowVersion;

class FlowSubmissionValidator
{
    /**
     * Validate submission data against the Flow Version Schema.
     *
     * @param array $submissionData Key-value pair of field_id => user_input
     * @param WhatsAppFlowVersion $version The snapshot version of the flow
     * @return array ['isValid' => bool, 'errors' => array, 'cleanedData' => array]
     */
    public function validate(array $submissionData, WhatsAppFlowVersion $version)
    {
        $errors = [];
        $cleanedData = [];
        $design = $version->design_data;

        // Flatten all components from all screens to create a schema map
        $schema = [];
        foreach ($design['screens'] ?? [] as $screen) {
            foreach ($screen['components'] ?? [] as $comp) {
                if (isset($comp['name'])) {
                    $schema[$comp['name']] = $comp;
                }
            }
        }

        foreach ($schema as $fieldId => $config) {
            $value = $submissionData[$fieldId] ?? null;

            // 1. Required Check
            if (($config['required'] ?? false) && empty($value)) {
                $errors[$fieldId] = "Field '{$config['label']}' is required.";
                continue;
            }

            if (empty($value)) {
                // Skip further validation if optional and empty
                continue;
            }

            // 2. Type Checking
            switch ($config['type']) {
                case 'TextInput':
                case 'TextArea':
                    if (!is_string($value)) {
                        $errors[$fieldId] = "Invalid format. Expected text.";
                    }
                    // Future: Add Regex pattern check if defined in config
                    break;

                case 'Dropdown':
                case 'Select':
                case 'RadioGroup':
                    // Verify if value exists in options
                    $validOptions = array_column($config['options'] ?? [], 'value');
                    if (!in_array($value, $validOptions)) {
                        $errors[$fieldId] = "Invalid selection.";
                    }
                    break;

                case 'CheckboxGroup':
                    // Value should be array of selected IDs
                    if (!is_array($value)) {
                        $errors[$fieldId] = "Invalid format. Expected list of selections.";
                    } else {
                        $validOptions = array_column($config['options'] ?? [], 'value');
                        foreach ($value as $v) {
                            if (!in_array($v, $validOptions)) {
                                $errors[$fieldId] = "Invalid selection: $v";
                            }
                        }
                    }
                    break;
            }

            $cleanedData[$fieldId] = $value;
        }

        // 3. Extra Field Check (Strict Mode)
        // If submission contains fields NOT in schema? (Maybe ignore for now)

        return [
            'isValid' => empty($errors),
            'errors' => $errors,
            'cleanedData' => $cleanedData
        ];
    }
}
