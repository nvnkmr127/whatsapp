<?php

namespace App\Livewire\Developer;

use App\Models\Message;
use App\Models\WebhookSource;
use App\Models\WhatsappTemplate;
use App\Services\WebhookAuthService;
use App\Services\WebhookMappingService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
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
    public $search = '';
    public $platformFilter = '';
    public $statusFilter = '';
    public $perPage = 10;
    public $exportDateRange = 30; // Days back to include in exports (7, 30, or 90)
    public $exportStatusFilter = 'all'; // Filter exports by message status: all, sent, delivered, read, failed

    protected $queryString = ['search', 'platformFilter', 'statusFilter'];

    // Wizard State - tracks the step-by-step configuration process
    public $currentStep = 1;
    public $isCapturing = false;
    public $capturedPayload = null;
    public $lastPayloadId = null;
    public $showRawData = false;
    public $showWizardModal = false;

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
    public $otpParamIndex = 1;
    public $otpLength = 6;

    // For testing
    public $testPayload = '';
    public $testResult = null;
    public $showTestModal = false;
    public $testingSourceId = null;
    public $testMessageResult = null;

    // For source report modal
    public $showSourceReportModal = false;
    public $selectedSourceForReport = null;
    public $sourceReportFromDate = '';
    public $sourceReportToDate = '';
    public $sourceReportPerPage = 20;

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
        $this->auth_config = [
            'key' => Str::random(32),
            'header' => 'X-API-Key'
        ];
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
        $user = auth()->user();
        $team = $user->currentTeam ?? ($user->isSuperAdmin() ? \App\Models\Team::first() : null);

        if (!$team) {
            $this->dispatch('notify', 'Please select a team before creating a source.');
            $this->currentStep = 1;
            return;
        }

        $source = WebhookSource::create([
            'team_id' => $team->id,
            'name' => $this->name,
            'platform' => $this->platform,
            'auth_method' => $this->auth_method,
            'auth_config' => $this->auth_config,
            'is_active' => true,
        ]);
        $this->editingId = $source->id;
    }

    public function updatedPlatform($platform)
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

            // Auto-generate keys/secrets if missing
            if ($this->auth_method === 'api_key' && empty($this->auth_config['key'])) {
                $this->generateApiKey();
            } elseif ($this->auth_method === 'hmac' && empty($this->auth_config['secret'])) {
                $this->generateSecret();
            }

            // Set default header if not present in config but present in preset
            if (empty($this->auth_config['header']) && !empty($preset['auth_config']['header'])) {
                $this->auth_config['header'] = $preset['auth_config']['header'];
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
        $user = auth()->user();
        $team = $user->currentTeam;

        $query = WebhookSource::query();

        if ($team && !$user->is_super_admin) {
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
                    'status' => $payload->status,
                    'error_message' => $payload->error_message,
                    'mapped_data' => $payload->mapped_data,
                    'created_at' => $payload->created_at ? $payload->created_at->diffForHumans() : 'Unknown',
                ];
            })->values()->toArray();
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

    public function openNewSource()
    {
        \Illuminate\Support\Facades\Log::info("Opening new source wizard");
        $this->cancelEdit();
        $this->showWizardModal = true;
        $this->showLogsModal = false;
    }

    public function create()
    {
        $this->nextStep();
    }

    public function edit($id)
    {
        try {
            \Illuminate\Support\Facades\Log::info("Triggering edit for source ID: {$id}");
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
            $this->filtering_rules_ui = !empty($source->filtering_rules) ? $source->filtering_rules : [['field' => '', 'operator' => 'equals', 'value' => '']];
            $this->is_active = $source->is_active;

            // Load latest payload if available for context
            $latest = \App\Models\WebhookPayload::where('webhook_source_id', $id)->latest()->first();
            if ($latest) {
                $this->capturedPayload = $latest->payload;
            }

            // Set selected event type and pull mappings to top level for UI
            if (!empty($this->field_mappings)) {
                $this->selectedEventType = array_key_first($this->field_mappings);
                $currentMappings = $this->field_mappings[$this->selectedEventType] ?? [];

                // Populate templateParameters if empty in action_config but present in field_mappings
                if (empty($this->templateParameters)) {
                    foreach ($currentMappings as $key => $path) {
                        if (str_starts_with($key, 'param_')) {
                            $pos = substr($key, 6);
                            $this->templateParameters[$pos] = $path;
                        }
                    }
                }

                // Pull phone number to top level so wire:model="field_mappings.phone_number" works
                if (isset($currentMappings['phone_number'])) {
                    $this->field_mappings['phone_number'] = $currentMappings['phone_number'];
                }
            }

            // Extract action type and template parameters
            $this->actionType = $this->action_config['type'] ?? 'send_template';
            $this->selectedTemplateId = $this->action_config['template_id'] ?? null;

            // Sanitize parameter_mapping to handle malformed data (arrays instead of strings)
            $paramMapping = $this->action_config['parameter_mapping'] ?? [];
            $sanitized = [];
            foreach ($paramMapping as $key => $value) {
                if (is_array($value)) {
                    // Extract first element if it's an array (malformed data from earlier version)
                    $sanitized[$key] = is_string($value[0] ?? null) ? $value[0] : '';
                } else {
                    $sanitized[$key] = (string) $value;
                }
            }

            $this->templateParameters = !empty($sanitized) ? $sanitized : $this->templateParameters;
            $this->otpParamIndex = $this->action_config['otp_param_index'] ?? 1;
            $this->otpLength = $this->action_config['otp_length'] ?? 6;

            $this->currentStep = 1; // Start at step 1 when editing
            $this->loadMappingContext();
            $this->showWizardModal = true;
            $this->showLogsModal = false;
            $this->showTestModal = false;
            \Illuminate\Support\Facades\Log::info("Wizard modal shown for ID: {$id}. State: active.");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error in WebhookSourceManager@edit: " . $e->getMessage());
            \Illuminate\Support\Facades\Log::error($e->getTraceAsString());
            $this->dispatch('notify', 'Error: ' . $e->getMessage(), 'error');
        }
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

        // Build field mappings (nested by event type as required by the processing engine)
        $fieldMappings = [];
        $eventType = $this->selectedEventType ?: 'custom';
        $currentEventMappings = [];

        // 1. Add variable mappings
        foreach ($this->templateParameters as $position => $fieldPath) {
            if ($fieldPath) {
                $currentEventMappings["param_{$position}"] = $fieldPath;
            }
        }

        // 2. Add phone number mapping (from flattened UI state)
        if (isset($this->field_mappings['phone_number']) && $this->field_mappings['phone_number']) {
            $currentEventMappings['phone_number'] = $this->field_mappings['phone_number'];
        }

        // 3. Nest under event type if we have any mappings
        if (!empty($currentEventMappings)) {
            $fieldMappings[$eventType] = $currentEventMappings;
        }

        $source->update([
            'name' => $this->name,
            'platform' => $this->platform,
            'auth_method' => $this->auth_method,
            'auth_config' => $this->auth_config,
            'field_mappings' => $fieldMappings ?: $this->field_mappings,
            'transformation_rules' => $this->transformation_rules,
            'action_config' => $actionConfig,
            'is_active' => $this->is_active,
            'filtering_rules' => $this->filtering_rules_ui,
            'process_delay' => $this->process_delay,
        ]);

        $this->reset(['editingId', 'name', 'platform', 'auth_method', 'auth_config', 'field_mappings', 'transformation_rules', 'action_config', 'is_active', 'templateParameters', 'filtering_rules_ui', 'process_delay', 'currentStep', 'capturedPayload', 'showWizardModal']);
        $this->initializeDefaults();
        $this->dispatch('notify', 'Webhook source saved successfully.');
    }

    public function cancelEdit()
    {
        $this->reset(['editingId', 'name', 'platform', 'auth_method', 'auth_config', 'field_mappings', 'transformation_rules', 'action_config', 'is_active', 'templateParameters', 'showWizardModal']);
        $this->initializeDefaults();
    }

    public function delete($id)
    {
        $source = WebhookSource::findOrFail($id);
        $this->authorize('delete', $source);

        $source->delete();
        $this->dispatch('notify', 'Webhook source deleted successfully.');
    }

    public function duplicate($id)
    {
        $source = WebhookSource::findOrFail($id);
        $this->authorize('update', $source);

        $newSource = $source->replicate();
        $newSource->name = $source->name . ' (Copy)';
        $newSource->is_active = false; // Duplicated source should be inactive until user activates it
        $newSource->slug = null; // Clear slug to trigger new one generation

        // Reset stats for the new source
        $newSource->total_received = 0;
        $newSource->total_processed = 0;
        $newSource->total_failed = 0;

        $newSource->save();

        $this->dispatch('notify', 'Webhook source duplicated successfully (inactive by default).');
    }

    public function toggleStatus($id)
    {
        $source = WebhookSource::findOrFail($id);
        $this->authorize('update', $source);

        $source->update(['is_active' => !$source->is_active]);
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->platformFilter = '';
        $this->statusFilter = '';
        $this->perPage = 10;
        $this->resetPage();
    }

    public function openSourceReportModal($sourceId)
    {
        $team = auth()->user()->currentTeam;
        $query = WebhookSource::query();
        if ($team && !auth()->user()->isSuperAdmin()) {
            $query->where('team_id', $team->id);
        }
        $this->selectedSourceForReport = $query->findOrFail($sourceId);
        $this->sourceReportFromDate = '';
        $this->sourceReportToDate = '';
        $this->sourceReportPerPage = 20;
        $this->showSourceReportModal = true;
    }

    public function closeSourceReportModal()
    {
        $this->showSourceReportModal = false;
        $this->selectedSourceForReport = null;
    }

    public function getSourceReportStats()
    {
        if (!$this->selectedSourceForReport) {
            return [
                'targeted' => 0,
                'sent' => 0,
                'delivered' => 0,
                'read' => 0,
                'failed' => 0,
                'total' => 0,
            ];
        }

        $query = Message::query()
            ->where('webhook_source_id', $this->selectedSourceForReport->id)
            ->where('direction', 'outbound');

        if ($this->sourceReportFromDate) {
            $query->whereDate('sent_at', '>=', $this->sourceReportFromDate);
        }

        if ($this->sourceReportToDate) {
            $query->whereDate('sent_at', '<=', $this->sourceReportToDate);
        }

        return [
            'targeted' => (clone $query)->count(),
            'sent' => (clone $query)->whereIn('status', ['sent', 'delivered', 'read'])->count(),
            'delivered' => (clone $query)->whereIn('status', ['delivered', 'read'])->count(),
            'read' => (clone $query)->where('status', 'read')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'total' => (clone $query)->count(),
        ];
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

        if ($this->actionType === 'send_template' || $this->actionType === 'send_otp') {
            $config['template_id'] = $this->selectedTemplateId;
            $config['phone_field'] = 'phone_number';

            $paramMapping = [];
            foreach ($this->templateParameters as $position => $field) {
                if ($field) {
                    // Defensive: Ensure we're saving a string, not an array
                    if (is_array($field)) {
                        $field = is_string($field[0] ?? null) ? $field[0] : '';
                    }
                    // FIX: Save the actual field/path instead of the string "param_N"
                    $paramMapping[$position] = (string) $field;
                }
            }
            $config['parameter_mapping'] = $paramMapping;

            if ($this->actionType === 'send_otp') {
                $config['otp_param_index'] = $this->otpParamIndex;
                $config['otp_length'] = $this->otpLength;
            }
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
                'id' => 'evt_123',
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

        return $samples[$platform] ?? json_encode([
            'event' => 'order_placed',
            'order_id' => 'WEB-8899',
            'customer_name' => 'Demo User',
            'customer_phone' => '+1234567890',
            'amount' => '150.00',
            'email' => 'demo@example.com'
        ], JSON_PRETTY_PRINT);
    }

    // For logs viewer
    public $showLogsModal = false;
    public $logsSourceId = null;
    public $recentLogs = [];
    public $logsSourceStats = null;

    public function viewLogs($id)
    {
        try {
            \Illuminate\Support\Facades\Log::info("Opening logs for source ID: {$id}");
            $this->logsSourceId = $id;
            $this->showLogsModal = true;
            $this->showWizardModal = false;
            $this->showTestModal = false;
            $this->refreshLogs();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error opening logs: " . $e->getMessage());
            $this->dispatch('notify', 'Error loading logs: ' . $e->getMessage());
        }
    }

    public function refreshLogs()
    {
        if ($this->logsSourceId) {
            \Illuminate\Support\Facades\Log::info("Refreshing logs for source ID: {$this->logsSourceId}");
            $this->recentLogs = $this->getRecentPayloads($this->logsSourceId);
            \Illuminate\Support\Facades\Log::info("Found " . count($this->recentLogs) . " recent logs.");

            $source = WebhookSource::find($this->logsSourceId);
            if ($source) {
                $this->logsSourceStats = [
                    'received' => (int) $source->total_received,
                    'processed' => (int) $source->total_processed,
                    'failed' => (int) $source->total_failed,
                    'rate' => $source->getSuccessRate(),
                    'name' => $source->name
                ];
                \Illuminate\Support\Facades\Log::info("Stats updated: ", $this->logsSourceStats);

                // Force Livewire to update the view
                $this->dispatch('stats-loaded');
            } else {
                \Illuminate\Support\Facades\Log::warning("Source not found during refreshLogs for ID: {$this->logsSourceId}");
            }

            $this->dispatch('notify', 'Logs and analytics refreshed.');
        } else {
            \Illuminate\Support\Facades\Log::warning("refreshLogs called without logsSourceId");
        }
    }

    public function refreshMappingContext()
    {
        $this->loadMappingContext();
        $this->dispatch('notify', 'Mapping context refreshed from latest data.');
    }

    protected function loadMappingContext()
    {
        $payload = null;

        // 1. Try to use captured payload first
        if ($this->capturedPayload) {
            $payload = is_string($this->capturedPayload)
                ? json_decode($this->capturedPayload, true)
                : $this->capturedPayload;
        }

        // 2. Try to get latest payload from DB if editing and no captured payload yet
        if (empty($payload) && $this->editingId) {
            $latest = \App\Models\WebhookPayload::where('webhook_source_id', $this->editingId)
                ->latest()
                ->first();

            if ($latest && is_array($latest->payload)) {
                $payload = $latest->payload;
            }
        }

        // 3. Fallback to platform-specific sample if still no payload found
        if (empty($payload)) {
            $sampleJson = $this->getSamplePayload($this->platform ?: 'custom');
            $payload = json_decode($sampleJson, true) ?: [];
        }

        // 4. Ultimate safety fallback to ensure dropdowns are NEVER empty (fixes "empty inbound events" report)
        if (empty($payload)) {
            $payload = [
                'event_type' => 'test_event',
                'customer_id' => 'CUST-100',
                'phone' => '+1234567890',
                'name' => 'Demo User',
                'amount' => '0.00'
            ];
        }

        // 5. Flatten the payload for the dropdown menus
        $flattened = Arr::dot($payload);

        // Ensure even flat keys are present and the array isn't empty
        $this->mappingContext = !empty($flattened) ? $flattened : $payload;

        // 6. Sync back to capturedPayload for the preview window if it was empty
        if (empty($this->capturedPayload)) {
            $this->capturedPayload = $payload;
        }

        // Log found context for debugging if needed
        if (empty($this->mappingContext)) {
            \Illuminate\Support\Facades\Log::warning("Mapping context remains empty for source {$this->editingId}");
        }
    }

    public function sendTestMessage()
    {
        $this->testMessageResult = null;

        try {
            if (!$this->selectedTemplateId) {
                $this->testMessageResult = ['type' => 'error', 'message' => 'Please select a template first.'];
                $this->dispatch('notify', 'Please select a template first.', 'error');
                return;
            }

            $phoneNumberMapping = $this->field_mappings['phone_number'] ?? null;
            if (!$phoneNumberMapping) {
                $this->testMessageResult = ['type' => 'error', 'message' => 'Please map or set a phone number.'];
                $this->dispatch('notify', 'Please map or set a phone number.', 'error');
                return;
            }

            // Get payload
            $payload = null;
            if ($this->capturedPayload) {
                $payload = is_string($this->capturedPayload) ? json_decode($this->capturedPayload, true) : $this->capturedPayload;
            }
            if (!$payload) {
                $sampleJson = $this->getSamplePayload($this->platform ?: 'custom');
                $payload = json_decode($sampleJson, true);
            }

            // Resolve Phone Number
            $phoneNumber = null;
            if (str_starts_with($phoneNumberMapping, 'STATIC:')) {
                $phoneNumber = substr($phoneNumberMapping, 7);
            } else {
                $phoneNumber = data_get($payload, $phoneNumberMapping);
            }

            if (!$phoneNumber) {
                $this->testMessageResult = ['type' => 'error', 'message' => 'Could not determine phone number from payload or static value.'];
                $this->dispatch('notify', 'Could not determine phone number from payload or static value.', 'error');
                return;
            }

            $template = WhatsappTemplate::find($this->selectedTemplateId);

            // Resolve Parameters
            $parameters = [];
            if (!empty($this->templateParameters)) {
                $keys = array_keys($this->templateParameters);
                $maxParam = !empty($keys) ? max($keys) : 0;
                for ($i = 1; $i <= $maxParam; $i++) {
                    $path = $this->templateParameters[$i] ?? null;
                    if ($path) {
                        if (str_starts_with($path, 'STATIC:')) {
                            $parameters[] = substr($path, 7);
                        } else {
                            // Handle nested arrays in payload gracefully
                            $val = data_get($payload, $path);
                            $parameters[] = is_array($val) ? json_encode($val) : (string) $val;
                        }
                    } else {
                        $parameters[] = '';
                    }
                }
            }

            $whatsappService = new \App\Services\WhatsAppService($template->team);
            $response = $whatsappService->sendTemplate(
                $phoneNumber,
                $template->name,
                $template->language ?? 'en_US',
                $parameters
            );

            if ($response['success']) {
                $this->testMessageResult = ['type' => 'success', 'message' => 'Message sent to ' . $phoneNumber];
                $this->dispatch('notify', 'Test message sent successfully to ' . $phoneNumber);
            } else {
                $errorDesc = $response['message'] ?? (is_array($response['error']) ? json_encode($response['error']) : ($response['error'] ?? 'Unknown error'));
                $this->testMessageResult = ['type' => 'error', 'message' => 'Failed: ' . $errorDesc];
                $this->dispatch('notify', 'Failed: ' . $errorDesc, 'error');
            }

        } catch (\Exception $e) {
            $this->testMessageResult = ['type' => 'error', 'message' => 'Error: ' . $e->getMessage()];
            \Illuminate\Support\Facades\Log::error("Test send error: " . $e->getMessage());
            $this->dispatch('notify', 'Error: ' . $e->getMessage(), 'error');
        }
    }

    public function exportWebhookReport()
    {
        return $this->streamWebhookExport($this->normalizeExportStatusFilter());
    }

    public function exportFailedWebhookReport()
    {
        return $this->streamWebhookExport('failed');
    }

    public function exportWebhookReportForSource(int $sourceId)
    {
        return $this->streamWebhookExportForSource($sourceId, $this->normalizeExportStatusFilter());
    }

    public function exportFailedWebhookReportForSource(int $sourceId)
    {
        return $this->streamWebhookExportForSource($sourceId, 'failed');
    }

    protected function streamWebhookExport(string $statusFilter)
    {
        $teamId = $this->resolveExportTeamId();
        if (!$teamId) {
            $this->dispatch('notify', 'Please select a team to export webhook report.', 'error');
            return null;
        }

        $startDate = now()->subDays((int) $this->exportDateRange);

        return response()->streamDownload(function () use ($teamId, $startDate, $statusFilter) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Created At',
                'Message ID',
                'Direction',
                'Status',
                'Contact Name',
                'Contact Number',
                'Contact Email',
                'Sent At',
                'Delivered At',
                'Read At',
                'Error Message',
                'Message Content',
            ]);

            Message::query()
                ->with('contact:id,name,phone_number,email')
                ->where('team_id', $teamId)
                ->where('direction', 'outbound')
                ->where('created_at', '>=', $startDate)
                ->whereIn('status', ['sent', 'delivered', 'read', 'failed'])
                ->when($statusFilter !== 'all', function ($query) use ($statusFilter) {
                    $query->where('status', $statusFilter);
                })
                ->orderByDesc('created_at')
                ->chunk(500, function ($messages) use ($handle) {
                    foreach ($messages as $message) {
                        fputcsv($handle, [
                            optional($message->created_at)->format('Y-m-d H:i:s'),
                            $message->whatsapp_message_id,
                            $message->direction,
                            $message->status,
                            optional($message->contact)->name,
                            optional($message->contact)->phone_number,
                            optional($message->contact)->email,
                            optional($message->sent_at)->format('Y-m-d H:i:s'),
                            optional($message->delivered_at)->format('Y-m-d H:i:s'),
                            optional($message->read_at)->format('Y-m-d H:i:s'),
                            $message->error_message,
                            $message->content,
                        ]);
                    }
                });

            fclose($handle);
        }, $statusFilter === 'failed' ? 'webhook-failed-report.csv' : 'webhook-message-report.csv');
    }

    protected function streamWebhookExportForSource(int $sourceId, string $statusFilter)
    {
        $source = WebhookSource::findOrFail($sourceId);
        $this->authorize('view', $source);

        $startDate = now()->subDays((int) $this->exportDateRange);

        return response()->streamDownload(function () use ($source, $startDate, $statusFilter) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Source Name',
                'Created At',
                'Message ID',
                'Direction',
                'Status',
                'Contact Name',
                'Contact Number',
                'Contact Email',
                'Sent At',
                'Delivered At',
                'Read At',
                'Error Message',
                'Message Content',
            ]);

            Message::query()
                ->with('contact:id,name,phone_number,email')
                ->where('team_id', $source->team_id)
                ->where('webhook_source_id', $source->id)
                ->where('direction', 'outbound')
                ->where('created_at', '>=', $startDate)
                ->whereIn('status', ['sent', 'delivered', 'read', 'failed'])
                ->when($statusFilter !== 'all', function ($query) use ($statusFilter) {
                    $query->where('status', $statusFilter);
                })
                ->orderByDesc('created_at')
                ->chunk(500, function ($messages) use ($handle, $source) {
                    foreach ($messages as $message) {
                        fputcsv($handle, [
                            $source->name,
                            optional($message->created_at)->format('Y-m-d H:i:s'),
                            $message->whatsapp_message_id,
                            $message->direction,
                            $message->status,
                            optional($message->contact)->name,
                            optional($message->contact)->phone_number,
                            optional($message->contact)->email,
                            optional($message->sent_at)->format('Y-m-d H:i:s'),
                            optional($message->delivered_at)->format('Y-m-d H:i:s'),
                            optional($message->read_at)->format('Y-m-d H:i:s'),
                            $message->error_message,
                            $message->content,
                        ]);
                    }
                });

            fclose($handle);
        }, $statusFilter === 'failed'
            ? 'webhook-source-' . $source->id . '-failed.csv'
            : 'webhook-source-' . $source->id . '-report.csv');
    }

    protected function resolveExportTeamId(): ?int
    {
        $user = auth()->user();
        $team = $user->currentTeam;

        if ($team) {
            return (int) $team->id;
        }

        if ($user->is_super_admin) {
            return (int) optional(\App\Models\Team::first())->id;
        }

        return null;
    }

    protected function normalizeExportStatusFilter(): string
    {
        $allowed = ['all', 'sent', 'delivered', 'read', 'failed'];
        return in_array($this->exportStatusFilter, $allowed, true) ? $this->exportStatusFilter : 'all';
    }



    #[Computed]
    public function currentSource()
    {
        if (!$this->editingId) return null;
        return WebhookSource::find($this->editingId);
    }

    public function render()
    {
        \Illuminate\Support\Facades\Log::info("Rendering WebhookSourceManager. Wizards: " . ($this->showWizardModal ? 'YES' : 'NO') . " | Logs: " . ($this->showLogsModal ? 'YES' : 'NO'));
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

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('platform', 'like', '%' . $this->search . '%')
                    ->orWhereHas('team', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        if ($this->platformFilter) {
            $query->where('platform', $this->platformFilter);
        }

        if ($this->statusFilter !== '') {
            $query->where('is_active', (bool) $this->statusFilter);
        }

        $sources = $query->with(['payloads', 'team'])
            ->withCount([
                'messages as msg_attempted_count' => function ($q) {
                    $q->where('direction', 'outbound')
                        ->whereIn('status', ['sent', 'delivered', 'read', 'failed']);
                },
                'messages as msg_sent_count' => function ($q) {
                    $q->where('direction', 'outbound')
                        ->whereIn('status', ['sent', 'delivered', 'read']);
                },
                'messages as msg_delivered_count' => function ($q) {
                    $q->where('direction', 'outbound')
                        ->whereIn('status', ['delivered', 'read']);
                },
                'messages as msg_read_count' => function ($q) {
                    $q->where('direction', 'outbound')
                        ->where('status', 'read');
                },
                'messages as msg_failed_count' => function ($q) {
                    $q->where('direction', 'outbound')
                        ->where('status', 'failed');
                },
            ])
            ->latest()
            ->paginate((int) $this->perPage);

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

        return view('livewire.developer.webhook-source-manager', compact('sources', 'platforms', 'templates', 'templateParams', 'selectedTemplate'))
            ->with('sourceReportStats', $this->getSourceReportStats());
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPlatformFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }
}
