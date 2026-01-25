<?php

namespace App\Services;

use App\Models\Team;
use App\Models\WhatsAppFlow;
use App\Models\WhatsAppFlowResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppFlowService
{
    protected $whatsappService;
    protected $baseUrl;
    protected $team;
    protected $token;
    protected $wabaId;

    public function __construct(WhatsAppService $whatsappService, Team $team = null)
    {
        $this->whatsappService = $whatsappService;
        $this->baseUrl = config('whatsapp.base_url', 'https://graph.facebook.com') . '/' . config('whatsapp.api_version', 'v21.0');
        if ($team) {
            $this->setTeam($team);
        }
    }

    public const CATEGORIES = [
        'SIGN_UP',
        'SIGN_IN',
        'APPOINTMENT_BOOKING',
        'LEAD_GENERATION',
        'CONTACT_US',
        'CUSTOMER_SUPPORT',
        'SURVEY',
        'OTHER'
    ];

    public function setTeam(Team $team)
    {
        $this->team = $team;
        $this->token = $team->whatsapp_access_token;
        $this->wabaId = $team->whatsapp_business_account_id;

        if (!$this->token || !$this->wabaId) {
            throw new \Exception("WhatsApp Business Account credentials missing.");
        }

        return $this;
    }

    /**
     * Create a new Flow on Meta.
     */
    public function createFlowOnMeta(WhatsAppFlow $flow)
    {
        $response = Http::withToken((string) $this->token)
            ->post("{$this->baseUrl}/{$this->wabaId}/flows", [
                'name' => $flow->name,
                'categories' => [$flow->category ?? 'OTHER'],
            ]);

        if ($response->failed()) {
            throw new \Exception("Meta Flow Creation Failed: " . $response->body());
        }

        $data = $response->json();
        $flow->update(['flow_id' => $data['id']]);

        return $data['id'];
    }

    /**
     * Update Flow Assets (JSON structure) via Multipart Upload.
     */
    public function updateFlowDesign(WhatsAppFlow $flow, array $designData)
    {
        // 0. Safety Check: Validate against Meta Policies
        $this->validateContentPolicy($designData);

        // 1. Generate Meta-compatible JSON (v3.0/v6.0)
        $metaJson = $this->generateMetaJson([
            'screens' => $designData['screens']
        ], $flow->uses_data_endpoint);

        $jsonContent = json_encode($metaJson, JSON_PRETTY_PRINT);

        // 2. Create a temporary file for the upload
        $tempPath = sys_get_temp_dir() . '/flow_' . $flow->id . '.json';
        file_put_contents($tempPath, $jsonContent);

        // 3. Upload to Meta Assets Endpoint (multipart/form-data)
        $response = Http::withToken((string) $this->token)
            ->attach('file', file_get_contents($tempPath), 'flow.json', ['Content-Type' => 'application/json'])
            ->post("{$this->baseUrl}/{$flow->flow_id}/assets", [
                'name' => 'flow.json',
                'asset_type' => 'FLOW_JSON',
            ]);

        // Cleanup
        @unlink($tempPath);

        if ($response->failed()) {
            throw new \Exception("Meta Asset Upload Failed: " . $response->body());
        }

        $result = $response->json();

        // Check for validation errors from Meta
        if (isset($result['validation_errors']) && !empty($result['validation_errors'])) {
            $errors = json_encode($result['validation_errors']);
            throw new \Exception("Flow Validation Errors: {$errors}");
        }

        if (!$result['success']) {
            throw new \Exception("Unknown Meta Error: " . json_encode($result));
        }

        // Save local copy
        $flow->update(['flow_json' => $metaJson]);

        return true;
    }

    /**
     * Publish the Flow and Create Version Snapshot.
     */
    public function publishFlow(WhatsAppFlow $flow)
    {
        // 1. Trigger Publish On Meta
        $response = Http::withToken((string) $this->token)
            ->post("{$this->baseUrl}/{$flow->flow_id}/publish");

        if ($response->failed()) {
            throw new \Exception("Meta Flow Publish Failed: " . $response->body());
        }

        // 2. Create Internal Version Snapshot
        $newVersionNumber = $flow->latest_version_number + 1;

        // Hash content for integrity checks
        $contentHash = md5(json_encode($flow->design_data) . json_encode($flow->flow_json));

        $version = \App\Models\WhatsAppFlowVersion::create([
            'whatsapp_flow_id' => $flow->id,
            'version_number' => $newVersionNumber,
            'status' => 'PUBLISHED',
            'version_hash' => $contentHash,
            'meta_publish_id' => $response->json()['id'] ?? null,
            'design_data' => $flow->design_data,
            'flow_json' => $flow->flow_json,
            'entry_point_config' => $flow->entry_point_config
        ]);

        // 3. Update Flow Head
        $flow->update([
            'status' => 'PUBLISHED', // Sync status
            'active_version_id' => $version->id,
            'latest_version_number' => $newVersionNumber
        ]);

        return $response->json();
    }

    /**
     * Convert visual builder data to Meta Flow JSON v3.0.
     */
    /**
     * Convert visual builder data to Meta Flow JSON v3.0 (or v6.0).
     */
    protected function generateMetaJson(array $design, bool $usesEndpoint = true)
    {
        $screens = [];
        $routing = [];
        $screenIds = array_column($design['screens'], 'id');
        $terminalScreens = [];

        // First pass: Identify screens and their components
        foreach ($design['screens'] as $index => $screen) {
            $screenId = $screen['id'];
            $isTerminal = false;

            // Determine next screen ID relative to current index (Linear Flow Assumption)
            // This is crucial: We are enforcing a linear flow (1->2->3) for simplicity 
            // to satisfy the "No Loops" and "Entry Screen" (Screen 1 has no inbound) rules.
            $nextScreenId = isset($design['screens'][$index + 1]) ? $design['screens'][$index + 1]['id'] : null;

            // Map components
            $children = [];
            $hasNavigationAction = false;
            $hasCompleteAction = false;

            foreach ($screen['components'] as $comp) {
                // Check actions
                if (isset($comp['on_click_action'])) {
                    if ($comp['on_click_action'] === 'complete') {
                        $hasCompleteAction = true;
                    } elseif ($comp['on_click_action'] === 'next') {
                        $hasNavigationAction = true;
                    }
                }

                // Map
                $mapped = $this->mapComponent($comp, $nextScreenId);
                if ($mapped)
                    $children[] = $mapped;
            }

            // Logic to determine if this screen is strictly terminal
            // If it has a 'complete' action, it IS terminal.
            // If it has 'next' action but NO next screen, it MUST be terminal (fallback).
            if ($hasCompleteAction) {
                $isTerminal = true;
            } elseif ($hasNavigationAction && !$nextScreenId) {
                $isTerminal = true;
            } else if (!$hasNavigationAction && !$hasCompleteAction) {
                // Screen with no actions? Maybe strict validation fails, but let's assume valid static screen? 
                // No, a screen must have navigation. 
                // Let's assume if it's the last one, it's terminal.
                if (!$nextScreenId)
                    $isTerminal = true;
            }

            // Screen Definition
            $screenDef = [
                'id' => $screenId,
                'title' => $screen['title'],
                'data' => (object) [],
                'layout' => [
                    'type' => 'SingleColumnLayout',
                    'children' => $children
                ],
                'terminal' => $isTerminal
            ];

            if ($isTerminal) {
                $screenDef['success'] = true;
                $terminalScreens[] = $screenId;
                // Terminal screens MUST NOT have routing entries in strict mode? 
                // Docs say: "Routes can be empty for a screen if there is no forward route from it."
                // "All routes must end at the terminal screen."
                $routing[$screenId] = [];
            } else {
                // Non-terminal screens must route somewhere. 
                // In our linear model, they route to the next screen.
                if ($nextScreenId) {
                    $routing[$screenId] = [$nextScreenId];
                } else {
                    // Startling case: Non-terminal but no next screen? 
                    // Should have been caught by "fallback terminal" logic above.
                    $routing[$screenId] = [];
                }
            }

            // Special handling: "complete" action component MUST be on a terminal screen.
            // Our logic above ensures $isTerminal=true if 'complete' action exists.

            $screens[] = $screenDef;

            // Validate Media constraints
            // ... (keeping existing validation logic if needed, or assuming simplified for now)
            // Re-adding simple media validation logic here to be safe and complete:
            $mediaPickers = 0;
            $images = 0;
            foreach ($children as $c) {
                if (in_array($c['type'], ['PhotoPicker', 'DocumentPicker']))
                    $mediaPickers++;
                if ($c['type'] === 'Image')
                    $images++;
            }
            if ($mediaPickers > 1)
                throw new \Exception("Constraint Violation: Multiple Media Pickers in '{$screen['title']}'");
            if ($images > 3)
                throw new \Exception("Constraint Violation: Too many images in '{$screen['title']}'");
        }

        // Validate Routing: Ensure Entry Screen exists (screen with no inbound edges)
        // In our linear logic ($routing[$id] = [$nextId]), Screen[0] is never a target, so it is the entry.
        // Screen[1] is target of Screen[0], etc.
        // This guarantees: 
        // 1. One Entry Screen (Screen[0])
        // 2. No Loops (forward only)
        // 3. Termination (last screen is terminal)

        $json = [
            'version' => '6.0',
            'screens' => $screens
        ];

        if ($usesEndpoint) {
            $json['data_api_version'] = '3.0';
            $json['routing_model'] = (object) $routing;
        }

        return $json;
    }

    protected function mapComponent($comp, $nextScreenId = null)
    {
        $type = $comp['type'];

        switch ($type) {
            case 'TextBody':
                return [
                    'type' => 'TextBody',
                    'text' => $comp['text']
                ];
            // ... (keep intermediate cases same, skip to Footer)

            // Actually I shouldn't replace the whole thing if I can help it, but mapComponent start is far away.
            // I'll replace the Switch start and the Footer case separately? No, function signature needs change.
            // I will replace the start of mapComponent first.

            case 'TextInput':
                return [
                    'type' => 'TextInput',
                    'name' => $comp['name'],
                    'label' => $comp['label'],
                    'required' => $comp['required'] ?? false,
                    'input-type' => 'text' // Default
                ];
            case 'TextArea':
                return [
                    'type' => 'TextArea', // Meta supports TextArea
                    'name' => $comp['name'],
                    'label' => $comp['label'],
                    'required' => $comp['required'] ?? false
                ];
            case 'CheckboxGroup':
                return [
                    'type' => 'CheckboxGroup',
                    'name' => $comp['name'],
                    'label' => $comp['label'],
                    'required' => $comp['required'] ?? false,
                    'min-selected-items' => 1,
                    'max-selected-items' => 5,
                    'data-source' => array_map(function ($opt) {
                        return ['id' => $opt['value'], 'title' => $opt['label']];
                    }, $comp['options'] ?? [])
                ];
            case 'RadioGroup':
                return [
                    'type' => 'RadioButtonsGroup', // Meta name
                    'name' => $comp['name'],
                    'label' => $comp['label'],
                    'required' => $comp['required'] ?? false,
                    'data-source' => array_map(function ($opt) {
                        return ['id' => $opt['value'], 'title' => $opt['label']];
                    }, $comp['options'] ?? [])
                ];
            case 'Select':
            case 'Dropdown':
                return [
                    'type' => 'Dropdown',
                    'name' => $comp['name'],
                    'label' => $comp['label'],
                    'required' => $comp['required'] ?? false,
                    'data-source' => array_map(function ($opt) {
                        return ['id' => $opt['value'], 'title' => $opt['label']];
                    }, $comp['options'] ?? [])
                ];
                return [
                    'type' => 'DatePicker',
                    'name' => $comp['name'],
                    'label' => $comp['label'],
                    'required' => $comp['required'] ?? false,
                ];
            case 'PhotoPicker':
                return [
                    'type' => 'PhotoPicker',
                    'name' => $comp['name'],
                    'label' => $comp['label'],
                    'required' => $comp['required'] ?? false,
                    'photo-source' => $comp['photo_source'] ?? 'camera,gallery',
                    'max-file-size' => 25 * 1024 * 1024 // 25MB Max
                ];
            case 'DocumentPicker':
                return [
                    'type' => 'DocumentPicker',
                    'name' => $comp['name'],
                    'label' => $comp['label'],
                    'required' => $comp['required'] ?? false,
                    'max-file-size' => 25 * 1024 * 1024, // 25MB Max
                    'allowed-types' => $comp['allowed_types'] ?? ['application/pdf', 'image/jpeg', 'image/png']
                ];
                return [
                    'type' => 'DocumentPicker',
                    'name' => $comp['name'],
                    'label' => $comp['label'],
                    'required' => $comp['required'] ?? false,
                    'max-file-size' => 25 * 1024 * 1024, // 25MB Max
                    'allowed-types' => $comp['allowed_types'] ?? ['application/pdf', 'image/jpeg', 'image/png']
                ];
            case 'Image':
                return [
                    'type' => 'Image',
                    'src' => $comp['src'], // Must be HTTPS URL
                    'height' => (int) ($comp['height'] ?? 200),
                    'scale-type' => 'cover' // Default
                ];
            case 'Footer':
                // Meta Footer is valid.
                // It needs 'on-click-action' which maps to specific Meta actions.
                $action = [];
                if (isset($comp['on_click_action'])) {
                    if ($comp['on_click_action'] === 'complete') {
                        $action = [
                            'name' => 'complete',
                            'payload' => (object) [] // Empty object for payload
                        ];
                    } elseif ($comp['on_click_action'] === 'next') {
                        // "next" implies navigation to the sequential next screen
                        if ($nextScreenId) {
                            $action = [
                                'name' => 'navigate',
                                'next' => [
                                    'type' => 'screen',
                                    'name' => $nextScreenId
                                ],
                                'payload' => (object) []
                            ];
                        } else {
                            // No next screen available? Fallback to complete.
                            $action = [
                                'name' => 'complete',
                                'payload' => (object) []
                            ];
                        }
                    }
                }

                return [
                    'type' => 'Footer',
                    'label' => $comp['label'],
                    'on-click-action' => $action
                ];
            default:
                return null;
        }
    }

    /**
     * Handle incoming Flow Data Request (Decrypted).
     */
    /**
     * Handle incoming Flow Data Request (Decrypted).
     */
    public function handleRequest(array $request)
    {
        $action = $request['action'] ?? null;

        // Log raw request for debugging (in prod, mask sensitive data)
        Log::info("Flow Handle Request: " . $action, $request);

        switch ($action) {
            case 'INIT':
                // INIT request is used to load initial data for the Flow.
                return [
                    'screen' => 'screen_welcome', // Optional: force start screen? usually not needed for v3
                    'data' => []
                ];

            case 'data_exchange':
                $flowToken = $request['flow_token'] ?? null;
                $submissionData = $request['data'] ?? [];

                // 1. Context Resolution
                $tokenData = json_decode($flowToken, true);
                if (!$tokenData || !isset($tokenData['id'])) {
                    // Fallback check if token is just ID string (Legacy)
                    if (is_numeric($flowToken)) {
                        $flowId = $flowToken;
                        $versionNum = null;
                    } else {
                        Log::error("Invalid Flow Token format", ['token' => $flowToken]);
                        throw new \Exception("Invalid Flow Token");
                    }
                } else {
                    $flowId = $tokenData['id'];
                    $versionNum = $tokenData['v'] ?? null;
                }

                // 2. Load Version Snapshot
                $version = null;
                if ($versionNum) {
                    $version = \App\Models\WhatsAppFlowVersion::where('whatsapp_flow_id', $flowId)
                        ->where('version_number', $versionNum)
                        ->first();
                }

                // Fallback to active version if specific version not found (or not provided)
                if (!$version) {
                    $flow = WhatsAppFlow::where('id', $flowId)->where('team_id', $tokenData['t'] ?? $request['team_id'] ?? null)->first();
                    if ($flow && $flow->active_version_id) {
                        $version = $flow->activeVersion;
                    }
                }

                if (!$version) {
                    // Critical Error: No definition found.
                    // Return a generic error to the user interface in WhatsApp
                    return [
                        'screen' => 'SUCCESS', // Fallback? Or TERMINAL error screen?
                        'data' => [
                            'extension_error' => 'Flow definition not found. Please contact support.'
                        ]
                    ];
                }

                // 3. Downstream Enforcement: Schema Validation
                $validator = new \App\Validators\FlowSubmissionValidator();
                $validation = $validator->validate($submissionData, $version);

                if (!$validation['isValid']) {
                    // Return Validation Errors to Flow (Interactive Feedback)
                    // The error structure matches Meta's expectation for field errors
                    // However, for simple flows, we might just want to show a toast or error screen.
                    // Using standard error response for data_exchange
                    return [
                        'error_message' => 'Please fix the errors below.',
                        'field_errors' => $validation['errors']
                    ];
                }

                // 4. Policy Enforcement (Commerce/Sensitive Data)
                // Re-check for policy violations in the submitted data (e.g. if I missed it in design)
                // This is a second line of defense.
                // For instance, simple regex check on values for Credit Card patterns
                foreach ($validation['cleanedData'] as $key => $val) {
                    if (is_string($val) && strlen($val) > 12) {
                        if (preg_match('/\b(?:\d[ -]*?){13,16}\b/', $val)) {
                            // Possible CC number?
                            // Block it.
                            Log::warning("Potential Sensitive Data (PCI) blocked in flow submission", ['flow_id' => $flowId]);
                            return [
                                'error_message' => 'Security Alert: Payment information cannot be processed in this form.'
                            ];
                        }
                    }
                }

                // 5. Success - Data is valid and safe.
                // Store response
                WhatsAppFlowResponse::create([
                    'whatsapp_flow_id' => $flowId,
                    'whatsapp_flow_version_id' => $version->id,
                    'contact_id' => $tokenData['c'] ?? null,
                    'response_data' => $validation['cleanedData']
                ]);

                // Trigger automation
                try {
                    $contact = \App\Models\Contact::find($tokenData['c'] ?? null);
                    if ($contact) {
                        $this->whatsappService->setTeam($this->team)->sendText($contact->phone_number, "Thank you! We've received your form submission.");
                    }
                } catch (\Exception $e) {
                    Log::error('Flow Automation Trigger Failed: ' . $e->getMessage());
                }

                // Determine next step
                // Ideally, we look at the routing logic. For now, we assume this is the final submit.

                return [
                    'screen' => 'SUCCESS', // Assume a terminal success screen exists or logic handles it
                    'data' => [
                        'extension_message_response' => [
                            'params' => [
                                'flow_token' => $flowToken,
                            ]
                        ]
                    ]
                ];

            case 'ping':
                return [
                    'data' => [
                        'status' => 'active'
                    ]
                ];

            default:
                throw new \Exception("Unknown Flow Action: {$action}");
        }
    }

    /**
     * Fetch all flows from Meta WABA.
     */
    public function getFlowsFromMeta()
    {
        $response = Http::withToken((string) $this->token)
            ->get("{$this->baseUrl}/{$this->wabaId}/flows", [
                'fields' => 'id,name,status,categories',
                'limit' => 100
            ]);

        if ($response->failed()) {
            throw new \Exception("Failed to fetch flows from Meta: " . $response->body());
        }

        return $response->json()['data'] ?? [];
    }

    /**
     * Validate Flow content against Meta's Commerce & Usage Policies.
     * Prevents common violations like asking for payment info or passwords directly.
     */
    protected function validateContentPolicy(array $design)
    {
        $restrictedKeywords = [
            'credit card',
            'debit card',
            'cvv',
            'cvc',
            'payment card',
            'bank account number',
            'account no',
            'routing number',
            'passport number',
            'driver license',
            'social security',
            'ssn',
            'password',
            'secret code',
            'pin code',
            'health',
            'medical',
            'prescription',
            'patient'
        ];

        foreach ($design['screens'] ?? [] as $screen) {
            foreach ($screen['components'] ?? [] as $comp) {
                // Check Label and Text
                $textToCheck = ($comp['label'] ?? '') . ' ' . ($comp['text'] ?? '') . ' ' . ($comp['name'] ?? '');
                $textLower = strtolower($textToCheck);

                foreach ($restrictedKeywords as $keyword) {
                    if (str_contains($textLower, $keyword)) {
                        throw new \Exception("Policy Violation Warning: Your flow contains restricted term '{$keyword}'. Asking for payment details, government IDs, or health info directly in a Flow is prohibited by Meta and risks a permanent ban. Please use a secure external checkout or specialized components.");
                    }
                }
            }
        }
    }
    /**
     * Fetch and Parse Flow JSON from Meta.
     */
    public function getFlowJson(string $flowId)
    {
        // 1. Get Assets List
        $response = Http::withToken((string) $this->token)
            ->get("{$this->baseUrl}/{$flowId}/assets");

        if ($response->failed()) {
            throw new \Exception("Failed to fetch flow assets: " . $response->body());
        }

        $assets = $response->json()['data'] ?? [];
        $jsonAsset = null;

        foreach ($assets as $asset) {
            if ($asset['asset_type'] === 'FLOW_JSON') {
                $jsonAsset = $asset;
                break;
            }
        }

        if (!$jsonAsset) {
            return null; // No design found
        }

        // 2. Download valid JSON content
        $jsonContent = Http::get($jsonAsset['download_url'])->body();
        return json_decode($jsonContent, true);
    }

    /**
     * Convert Meta Flow JSON to Internal Builder Format.
     */
    public function convertMetaToInternal(array $metaJson)
    {
        $screens = [];
        $metaScreens = $metaJson['screens'] ?? [];

        foreach ($metaScreens as $screen) {
            $layoutChildren = $screen['layout']['children'] ?? [];

            // Recursively flatten the list of components
            $components = $this->flattenComponents($layoutChildren);

            $screens[] = [
                'id' => $screen['id'],
                'title' => $screen['title'] ?? $screen['id'],
                'components' => $components
            ];
        }

        return ['screens' => $screens];
    }

    /**
     * Recursively extract components from layout children.
     */
    protected function flattenComponents(array $children)
    {
        $result = [];

        foreach ($children as $child) {
            // Check if this child acts as a container (has its own children)
            // e.g. Column, Box, etc.
            if (isset($child['children']) && is_array($child['children'])) {
                // Recursively get children
                $nested = $this->flattenComponents($child['children']);
                $result = array_merge($result, $nested);
            } else {
                // Try to map it
                $internalComp = $this->mapMetaComponentToInternal($child);
                if ($internalComp) {
                    $result[] = $internalComp;
                } else {
                    // Assuming Log is available, otherwise remove or replace with error_log
                    // Log::warning("Unknown Flow Component Type ignored: " . ($child['type'] ?? 'unknown'));
                }
            }
        }

        return $result;
    }

    protected function mapMetaComponentToInternal($child)
    {
        $type = $child['type'] ?? 'unknown';
        $comp = [];

        // Common fields mapping
        if (isset($child['label']))
            $comp['label'] = $child['label'];
        if (isset($child['name']))
            $comp['name'] = $child['name'];
        if (isset($child['required']))
            $comp['required'] = $child['required'];

        switch ($type) {
            case 'TextHeading':
            case 'TextSubheading':
            case 'TextCaption':
            case 'TextBody':
                $comp['type'] = 'TextBody';
                $comp['text'] = $child['text'] ?? ($child['text-content'] ?? ''); // Meta variable naming check
                // If it's a heading, maybe prepend markdown?
                if ($type === 'TextHeading')
                    $comp['text'] = "**" . $comp['text'] . "**";
                // Ensure text is set
                if (!isset($comp['text']))
                    $comp['text'] = 'Text content';
                break;

            case 'TextInput':
                $comp['type'] = 'TextInput';
                break;

            case 'TextArea':
                $comp['type'] = 'TextArea';
                break;

            case 'CheckboxGroup':
                $comp['type'] = 'CheckboxGroup';
                $dataSource = $child['data-source'] ?? [];
                $comp['options'] = is_array($dataSource) ? array_map(function ($opt) {
                    return ['label' => $opt['title'], 'value' => $opt['id']];
                }, $dataSource) : [];
                break;

            case 'RadioButtonsGroup':
                $comp['type'] = 'RadioGroup';
                $dataSource = $child['data-source'] ?? [];
                $comp['options'] = is_array($dataSource) ? array_map(function ($opt) {
                    return ['label' => $opt['title'], 'value' => $opt['id']];
                }, $dataSource) : [];
                break;

            case 'Dropdown':
                $comp['type'] = 'Dropdown'; // Matches internal
                $dataSource = $child['data-source'] ?? [];
                $comp['options'] = is_array($dataSource) ? array_map(function ($opt) {
                    return ['label' => $opt['title'], 'value' => $opt['id']];
                }, $dataSource) : [];
                break;

            case 'DatePicker':
                $comp['type'] = 'DateField';
                break;

            case 'PhotoPicker':
                $comp['type'] = 'PhotoPicker';
                $comp['photo_source'] = $child['photo-source'] ?? 'camera,gallery';
                break;

            case 'DocumentPicker':
                $comp['type'] = 'DocumentPicker';
                $comp['allowed_types'] = $child['allowed-types'] ?? [];
                break;

            case 'Image':
                $comp['type'] = 'Image';
                $comp['src'] = $child['src'] ?? '';
                $comp['height'] = $child['height'] ?? 200;
                break;

            case 'Footer':
                $comp['type'] = 'Footer';
                $actionName = $child['on-click-action']['name'] ?? 'next';
                $comp['on_click_action'] = $actionName === 'complete' ? 'complete' : 'next';
                if (!isset($comp['label']))
                    $comp['label'] = $child['label'] ?? 'Next'; // Fallback
                break;

            case 'OptIn':
                // Map OptIn to a simple Checkbox for now as we don't have OptIn component in builder
                $comp['type'] = 'CheckboxGroup';
                $comp['options'] = [['label' => $child['label'] ?? 'Opt-in', 'value' => 'opt_in']];
                $comp['label'] = $child['label'] ?? 'Opt-in';
                break;

            default:
                return null;
        }

        return $comp;
    }
}
