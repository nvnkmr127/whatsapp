<?php

namespace App\Livewire\Developer;

use App\Models\WebhookSource;
use App\Models\WhatsappTemplate;
use App\Services\WebhookAuthService;
use App\Services\WebhookMappingService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class WebhookSourceManager extends Component
{
    use WithPagination;

    public $name, $platform = 'custom', $auth_method = 'api_key';
    public $auth_config = [];
    public $field_mappings = [];
    public $transformation_rules = [];
    public $action_config = [];
    public $is_active = true;
    public $editingId = null;

    // Wizard State
    public $currentStep = 1;
    public $isCapturing = false;
    public $capturedPayload = null;
    public $lastPayloadId = null;
    public $showRawData = false;

    // For filtering rules
    public $filtering_rules_ui = [];
    public $process_delay = 0;

    // For field mapping builder
    public $selectedEventType = '';
    public $mappingFields = [];
    public $mappingContext = []; // Flattened key-value pairs for dropdowns

    // For action configuration
    public $actionType = 'send_template';
    public $selectedTemplateId = null;
    public $templateParameters = [];

    // For testing
    public $testPayload = '';
    public $testResult = null;
    public $showTestModal = false;
    public $testingSourceId = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'platform' => 'required|string',
        'auth_method' => 'required|string',
        'is_active' => 'boolean',
    ];

    public function mount()
    {
        $this->initializeDefaults();
    }

    protected function initializeDefaults()
    {
        $this->auth_config = ['key' => Str::random(32)];
        $this->field_mappings = [];
        $this->transformation_rules = [];
        $this->action_config = [];
        $this->filtering_rules_ui = [['field' => '', 'operator' => 'equals', 'value' => '']];
        $this->process_delay = 0;
        $this->currentStep = 1;
        $this->isCapturing = false;
        $this->capturedPayload = null;
    }

    public function nextStep()
    {
        if ($this->currentStep === 1) {
            $this->validate();
            if (!$this->editingId) {
                $this->saveInitialSource();
            }
        }

        if ($this->currentStep < 4) {
            $this->currentStep++;
        }

        if ($this->currentStep === 3) {
            $this->loadMappingContext();
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function goToStep($step)
    {
        if ($step >= 1 && $step <= 4) {
            $this->currentStep = $step;
            if ($this->currentStep === 3) {
                $this->loadMappingContext();
            }
        }
    }

    public function startCapture()
    {
        if (!$this->editingId)
            return;

        $this->isCapturing = true;
        $latest = \App\Models\WebhookPayload::where('webhook_source_id', $this->editingId)
            ->latest()
            ->first();
        $this->lastPayloadId = $latest ? $latest->id : 0;
    }

    public function stopCapture()
    {
        $this->isCapturing = false;
    }

    public function checkForNewPayload()
    {
        if (!$this->isCapturing || !$this->editingId)
            return;

        $newPayload = \App\Models\WebhookPayload::where('webhook_source_id', $this->editingId)
            ->where('id', '>', $this->lastPayloadId)
            ->latest()
            ->first();

        if ($newPayload) {
            $this->isCapturing = false;
            $this->capturedPayload = $newPayload->payload;
            $this->dispatch('notify', 'New payload captured successfully!');
            $this->nextStep();
        }
    }

    protected function saveInitialSource()
    {
        $team = auth()->user()->currentTeam;

        if (!$team) {
            $this->dispatch('notify', 'Please select a team before creating a source.');
            return;
        }

        $source = WebhookSource::create([
            'team_id' => $team->id,
            'name' => $this->name,
            'platform' => $this->platform,
            'auth_method' => $this->auth_method,
            'auth_config' => json_encode($this->auth_config),
            'is_active' => true,
        ]);
        $this->editingId = $source->id;
    }

    public function selectPlatform($platform)
    {
        $this->platform = $platform;
        $preset = config("webhook-platforms.{$platform}");

        if ($preset) {
            $this->auth_method = $preset['auth_method'];
            $this->auth_config = $preset['auth_config'] ?? [];

            // Set sample mappings if available
            if (!empty($preset['sample_mappings'])) {
                $this->field_mappings = $preset['sample_mappings'];
                $this->selectedEventType = array_key_first($preset['sample_mappings']);
            }

            // Set sample transformations
            if (!empty($preset['sample_transformations'])) {
                $this->transformation_rules = $preset['sample_transformations'];
            }
        }

        $this->loadMappingContext();
    }

    public function generateApiKey()
    {
        $this->auth_config['key'] = Str::random(32);
    }

    public function generateSecret()
    {
        $this->auth_config['secret'] = bin2hex(random_bytes(32));
    }

    public function addMappingField()
    {
        if (!isset($this->field_mappings[$this->selectedEventType])) {
            $this->field_mappings[$this->selectedEventType] = [];
        }

        $this->field_mappings[$this->selectedEventType][''] = '';
    }

    public function removeMappingField($eventType, $internalField)
    {
        unset($this->field_mappings[$eventType][$internalField]);
    }

    public function updateFieldMapping($eventType, $oldKey, $newKey, $value)
    {
        if ($oldKey !== $newKey) {
            unset($this->field_mappings[$eventType][$oldKey]);
        }
        $this->field_mappings[$eventType][$newKey] = $value;
    }

    public function setActionType($type)
    {
        $this->actionType = $type;
        $this->action_config = ['type' => $type];
    }

    public function addFilterRule()
    {
        $this->filtering_rules_ui[] = ['field' => '', 'operator' => 'equals', 'value' => ''];
    }

    public function removeFilterRule($index)
    {
        unset($this->filtering_rules_ui[$index]);
        $this->filtering_rules_ui = array_values($this->filtering_rules_ui);
    }

    /**
     * Map incoming field to template parameter
     */
    public function mapFieldToParameter($parameterPosition, $fieldPath)
    {
        if (!isset($this->templateParameters)) {
            $this->templateParameters = [];
        }

        $this->templateParameters[$parameterPosition] = $fieldPath;
    }

    /**
     * Get recent webhook payloads for this source to show actual data
     */
    public function getRecentPayloads($sourceId)
    {
        $team = auth()->user()->currentTeam;

        $query = WebhookSource::query();

        if ($team) {
            $query->where('team_id', $team->id);
        }

        $source = $query->find($sourceId);

        if (!$source) {
            return [];
        }

        return $source->payloads()
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($payload) {
                return [
                    'id' => $payload->id,
                    'event_type' => $payload->event_type,
                    'payload' => $payload->payload,
                    'created_at' => $payload->created_at->diffForHumans(),
                ];
            });
    }

    /**
     * Extract all field paths from a payload (for visual mapping)
     */
    public function extractFieldPaths($payload, $prefix = '')
    {
        $paths = [];

        foreach ($payload as $key => $value) {
            $currentPath = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                // Recursively get nested paths
                $paths = array_merge($paths, $this->extractFieldPaths($value, $currentPath));
            } else {
                // Add this path with its value
                $paths[$currentPath] = $value;
            }
        }

        return $paths;
    }

    public function create()
    {
        $this->nextStep();
    }

    public function edit($id)
    {
        $source = WebhookSource::findOrFail($id);
        $this->authorize('update', $source);

        $this->editingId = $id;
        $this->name = $source->name;
        $this->platform = $source->platform;
        $this->auth_method = $source->auth_method;
        $this->auth_config = $source->getAuthConfig();
        $this->field_mappings = $source->field_mappings ?? [];
        $this->transformation_rules = $source->transformation_rules ?? [];
        $this->action_config = $source->action_config ?? [];
        $this->is_active = $source->is_active;
        $this->process_delay = $source->process_delay;
        $this->filtering_rules_ui = !empty($source->filtering_rules) ? $source->filtering_rules : [['field' => '', 'operator' => 'equals', 'value' => '']];

        // Set selected event type
        if (!empty($this->field_mappings)) {
            $this->selectedEventType = array_key_first($this->field_mappings);
        }

        // Extract action type and template parameters
        $this->actionType = $this->action_config['type'] ?? 'send_template';
        $this->selectedTemplateId = $this->action_config['template_id'] ?? null;
        $this->templateParameters = $this->action_config['parameter_mapping'] ?? [];

        $this->currentStep = 1; // Start at step 1 when editing
        $this->loadMappingContext();
    }

    public function update()
    {
        if ($this->currentStep < 4) {
            $this->nextStep();
            return;
        }

        $this->validate();

        $source = WebhookSource::findOrFail($this->editingId);
        $this->authorize('update', $source);

        $actionConfig = $this->buildActionConfig();

        // Build field mappings
        $fieldMappings = [];
        $eventType = $this->selectedEventType ?: 'custom';

        if (!empty($this->templateParameters)) {
            $fieldMappings[$eventType] = [];

            foreach ($this->templateParameters as $position => $fieldPath) {
                if ($fieldPath) {
                    $fieldMappings[$eventType]["param_{$position}"] = $fieldPath;
                }
            }

            if (isset($this->field_mappings['phone_number'])) {
                $fieldMappings[$eventType]['phone_number'] = $this->field_mappings['phone_number'];
            }
        }

        $source->update([
            'name' => $this->name,
            'platform' => $this->platform,
            'auth_method' => $this->auth_method,
            'auth_config' => json_encode($this->auth_config),
            'field_mappings' => $fieldMappings ?: $this->field_mappings,
            'transformation_rules' => $this->transformation_rules,
            'action_config' => $actionConfig,
            'is_active' => $this->is_active,
            'filtering_rules' => $this->filtering_rules_ui,
            'process_delay' => $this->process_delay,
        ]);

        $this->reset(['editingId', 'name', 'platform', 'auth_method', 'auth_config', 'field_mappings', 'transformation_rules', 'action_config', 'is_active', 'templateParameters', 'filtering_rules_ui', 'process_delay', 'currentStep', 'capturedPayload']);
        $this->initializeDefaults();
        $this->dispatch('notify', 'Webhook source saved successfully.');
    }

    public function cancelEdit()
    {
        $this->reset(['editingId', 'name', 'platform', 'auth_method', 'auth_config', 'field_mappings', 'transformation_rules', 'action_config', 'is_active', 'templateParameters']);
        $this->initializeDefaults();
    }

    public function delete($id)
    {
        $source = WebhookSource::findOrFail($id);
        $this->authorize('delete', $source);

        $source->delete();
        $this->dispatch('notify', 'Webhook source deleted successfully.');
    }

    public function toggleStatus($id)
    {
        $source = WebhookSource::findOrFail($id);
        $this->authorize('update', $source);

        $source->update(['is_active' => !$source->is_active]);
    }

    public function openTestModal($id)
    {
        $this->testingSourceId = $id;
        $this->showTestModal = true;
        $this->testResult = null;

        // Load sample payload based on platform
        $team = auth()->user()->currentTeam;
        $query = WebhookSource::query();
        if ($team) {
            $query->where('team_id', $team->id);
        }
        $source = $query->findOrFail($id);
        $this->testPayload = $this->getSamplePayload($source->platform);
    }

    public function testWebhook()
    {
        $source = WebhookSource::findOrFail($this->testingSourceId);

        try {
            $payload = json_decode($this->testPayload, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->testResult = ['error' => 'Invalid JSON payload'];
                return;
            }

            $mappingService = new WebhookMappingService();

            // Extract event type
            $eventType = $mappingService->extractEventType(
                $payload,
                config("webhook-platforms.{$source->platform}.event_type_path")
            );

            // Map fields
            $fieldMappings = $source->getFieldMapping($eventType);
            $mappedData = $mappingService->mapFields($payload, $fieldMappings);

            // Apply transformations
            $transformationRules = $source->getTransformationRules();
            if (!empty($transformationRules)) {
                $mappedData = $mappingService->transformData($mappedData, $transformationRules);
            }

            // Extract all available fields for visual mapping
            $availableFields = $this->extractFieldPaths($payload);

            $this->testResult = [
                'success' => true,
                'event_type' => $eventType,
                'mapped_data' => $mappedData,
                'available_fields' => $availableFields,
                'validation' => $mappingService->validateMappedData($mappedData),
            ];

        } catch (\Exception $e) {
            $this->testResult = ['error' => $e->getMessage()];
        }
    }

    protected function buildActionConfig()
    {
        $config = ['type' => $this->actionType];

        if ($this->actionType === 'send_template') {
            $config['template_id'] = $this->selectedTemplateId;

            $paramMapping = [];
            foreach ($this->templateParameters as $position => $field) {
                if ($field) {
                    $paramMapping[$position] = "param_{$position}";
                }
            }
            $config['parameter_mapping'] = $paramMapping;
            $config['phone_field'] = 'phone_number';
        }

        return $config;
    }

    protected function getSamplePayload($platform)
    {
        $samples = [
            'shopify' => json_encode([
                'id' => 12345,
                'order_number' => 'ORD-1001',
                'total_price' => '99.99',
                'currency' => 'USD',
                'customer' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'phone' => '+1234567890',
                    'email' => 'john@example.com',
                ],
            ], JSON_PRETTY_PRINT),
            'stripe' => json_encode([
                'type' => 'payment_intent.succeeded',
                'data' => [
                    'object' => [
                        'id' => 'pi_123',
                        'amount' => 5000,
                        'currency' => 'usd',
                        'customer_details' => [
                            'name' => 'Jane Smith',
                            'phone' => '+9876543210',
                        ],
                    ],
                ],
            ], JSON_PRETTY_PRINT),
        ];

        return $samples[$platform] ?? json_encode(['event' => 'test', 'data' => []], JSON_PRETTY_PRINT);
    }

    // For logs viewer
    public $showLogsModal = false;
    public $logsSourceId = null;
    public $recentLogs = [];

    public function viewLogs($id)
    {
        $this->logsSourceId = $id;
        $this->showLogsModal = true;
        $this->recentLogs = $this->getRecentPayloads($id);
    }

    public function refreshLogs()
    {
        if ($this->logsSourceId) {
            $this->recentLogs = $this->getRecentPayloads($this->logsSourceId);
            $this->dispatch('notify', 'Logs refreshed.');
        }
    }

    public function refreshMappingContext()
    {
        $this->loadMappingContext();
        $this->dispatch('notify', 'Mapping context refreshed from latest data.');
    }

    protected function loadMappingContext()
    {
        $payload = [];

        // 1. Try to use captured payload first
        if ($this->capturedPayload) {
            $payload = $this->capturedPayload;
        }
        // 2. Try to get latest payload from DB if editing
        elseif ($this->editingId) {
            $latest = \App\Models\WebhookPayload::where('webhook_source_id', $this->editingId)
                ->latest()
                ->first();

            if ($latest && is_array($latest->payload)) {
                $payload = $latest->payload;
            }
        }

        // 3. If no real payload, use sample from platform
        if (empty($payload) && $this->platform) {
            $sampleJson = $this->getSamplePayload($this->platform);
            $payload = json_decode($sampleJson, true) ?? [];
        }

        // 4. Flatten payload for dropdown (key => value)
        $this->mappingContext = !empty($payload) ? Arr::dot($payload) : [];
    }

    public function render()
    {
        $user = auth()->user();
        $team = $user->currentTeam;

        if (!$team && !$user->is_super_admin) {
            return <<<'HTML'
                <div class="p-8 text-center bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800">
                    <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">No Team Selected</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-2">Please select or create a team to manage webhook sources.</p>
                </div>
            HTML;
        }

        $query = WebhookSource::query();
        if ($team && !$user->is_super_admin) {
            $query->where('team_id', $team->id);
        }

        $sources = $query->with('payloads')
            ->latest()
            ->paginate(10);

        $platforms = config('webhook-platforms');

        $templates = [];
        if ($team) {
            $templates = WhatsappTemplate::where('team_id', $team->id)->get();
        } elseif ($user->is_super_admin) {
            $templates = WhatsappTemplate::all();
        }

        // Get template parameters for selected template
        $templateParams = [];
        $selectedTemplate = null;
        if ($this->selectedTemplateId) {
            $selectedTemplate = WhatsappTemplate::find($this->selectedTemplateId);
            if ($selectedTemplate && $selectedTemplate->components) {
                foreach ($selectedTemplate->components as $component) {
                    // Extract {{1}}, {{2}}, etc. from all components with text
                    if (isset($component['text'])) {
                        preg_match_all('/\{\{(\d+)\}\}/', $component['text'], $matches);
                        if (!empty($matches[1])) {
                            foreach ($matches[1] as $match) {
                                if (!in_array($match, $templateParams)) {
                                    $templateParams[] = $match;
                                }
                            }
                        }
                    }
                }
                sort($templateParams);
            }
        }

        return view('livewire.developer.webhook-source-manager', compact('sources', 'platforms', 'templates', 'templateParams', 'selectedTemplate'));
    }
}
