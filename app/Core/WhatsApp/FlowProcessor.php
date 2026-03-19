<?php

namespace App\Core\WhatsApp;

use App\Models\WhatsAppFlow;
use Illuminate\Support\Facades\Log;

class FlowProcessor
{
    /**
     * Convert visual builder data to Meta Flow JSON.
     */
    public function convertToMetaJson(array $design, bool $usesEndpoint = true): array
    {
        $screens = [];
        $routing = [];
        $terminalScreens = [];

        foreach ($design['screens'] as $index => $screen) {
            $screenId = $this->sanitizeId($screen['id']);
            $nextScreenId = isset($design['screens'][$index + 1]) ? $this->sanitizeId($design['screens'][$index + 1]['id']) : null;

            $children = [];
            $hasNavigationAction = false;
            $hasCompleteAction = false;

            foreach ($screen['components'] as $comp) {
                if (isset($comp['on_click_action'])) {
                    if ($comp['on_click_action'] === 'complete') $hasCompleteAction = true;
                    elseif ($comp['on_click_action'] === 'next') $hasNavigationAction = true;
                }
                $mapped = $this->mapComponent($comp, $nextScreenId);
                if ($mapped) $children[] = $mapped;
            }

            $isTerminal = $hasCompleteAction || ($hasNavigationAction && !$nextScreenId) || (!$hasNavigationAction && !$nextScreenId);

            $screenDef = [
                'id' => $screenId,
                'title' => $screen['title'],
                'data' => (object) [],
                'layout' => [ 'type' => 'SingleColumnLayout', 'children' => $children ],
                'terminal' => $isTerminal
            ];

            if ($isTerminal) {
                $screenDef['success'] = true;
                $terminalScreens[] = $screenId;
                $routing[$screenId] = [];
            } else {
                $routing[$screenId] = $nextScreenId ? [$nextScreenId] : [];
            }

            $screens[] = $screenDef;
        }

        $json = [ 'version' => '6.0', 'screens' => $screens ];
        if ($usesEndpoint) {
            $json['data_api_version'] = '3.0';
            $json['routing_model'] = (object) $routing;
        }

        return $json;
    }

    /**
     * Map visual components to Meta component structure.
     */
    protected function mapComponent(array $comp, ?string $nextScreenId = null): ?array
    {
        $type = $comp['type'];
        $base = [ 'type' => $type ];

        switch ($type) {
            case 'TextBody':
                return array_merge($base, [ 'text' => $comp['text'] ]);
            case 'TextInput':
            case 'TextArea':
                return array_merge($base, [
                    'name' => $this->sanitizeId($comp['name']),
                    'label' => $comp['label'],
                    'required' => $comp['required'] ?? false,
                ]);
            case 'CheckboxGroup':
            case 'RadioButtonsGroup':
            case 'Dropdown':
                return array_merge($base, [
                    'name' => $this->sanitizeId($comp['name']),
                    'label' => $comp['label'],
                    'required' => $comp['required'] ?? false,
                    'data-source' => array_map(function ($opt) {
                        return ['id' => $this->sanitizeId($opt['value']), 'title' => $opt['label']];
                    }, $comp['options'] ?? [])
                ]);
            case 'Footer':
                $action = [ 'name' => 'complete', 'payload' => (object) [] ];
                if (($comp['on_click_action'] ?? '') === 'next' && $nextScreenId) {
                    $action = [
                        'name' => 'navigate',
                        'next' => [ 'type' => 'screen', 'name' => $nextScreenId ],
                        'payload' => (object) []
                    ];
                }
                return array_merge($base, [ 'label' => $comp['label'], 'on-click-action' => $action ]);
            default:
                return null;
        }
    }

    /**
     * Deeply sanitize IDs in the design structure.
     */
    public function deepSanitize(array $design): array
    {
        if (!isset($design['screens'])) return $design;
        foreach ($design['screens'] as &$screen) {
            if (isset($screen['id'])) $screen['id'] = $this->sanitizeId($screen['id']);
            if (isset($screen['components'])) {
                foreach ($screen['components'] as &$comp) {
                    if (isset($comp['name'])) $comp['name'] = $this->sanitizeId($comp['name']);
                    if (isset($comp['options'])) {
                        foreach ($comp['options'] as &$opt) {
                            if (isset($opt['value'])) $opt['value'] = $this->sanitizeId($opt['value']);
                        }
                    }
                }
            }
        }
        return $design;
    }

    /**
     * Sanitize ID for Meta compliance (alphabets and underscores only).
     */
    protected function sanitizeId(?string $id): ?string
    {
        if (!$id) return $id;
        $map = [ '0' => 'g', '1' => 'h', '2' => 'i', '3' => 'j', '4' => 'k', '5' => 'l', '6' => 'm', '7' => 'n', '8' => 'o', '9' => 'p' ];
        return strtr((string) $id, $map);
    }
}
