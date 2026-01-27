<?php

namespace App\Livewire\Whatsapp;

use App\Models\CallSettings;
use App\Models\CallPermission;
use App\Services\WhatsAppService;
use Livewire\Component;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Log;

use Livewire\WithPagination;

#[Title('Call Settings')]
class CallSettingsManager extends Component
{
    use WithPagination;

    public $phoneNumberId;
    public $settings;

    // Search and Filter
    public $search = '';
    public $filterStatus = '';

    // Call Settings
    public $callingEnabled = false;
    public $callIconVisibility = 'show';
    public $callbackPermissionEnabled = false;

    // Business Hours
    public $timezone = 'UTC';
    public $businessHours = [];
    public $syncWithBusinessHours = false;

    // SIP Configuration
    public $sipEnabled = false;
    public $sipUri = '';
    public $sipUsername = '';
    public $sipPassword = '';
    public $sipRealm = '';

    // Statistics
    public $totalPermissions = 0;
    public $activePermissions = 0;
    public $expiredPermissions = 0;
    public $callsMadeToday = 0;
    public $isRestricted = false;
    public $restrictionReason = '';

    protected $rules = [
        'callingEnabled' => 'boolean',
        'callIconVisibility' => 'in:show,hide',
        'callbackPermissionEnabled' => 'boolean',
        'timezone' => 'string',
        'businessHours' => 'array',
        'sipUri' => 'nullable|string',
        'sipUsername' => 'nullable|string',
        'sipPassword' => 'nullable|string',
        'sipRealm' => 'nullable|string',
    ];

    public function mount()
    {
        $team = auth()->user()->currentTeam;
        $this->phoneNumberId = $team->whatsapp_phone_number_id;

        if (!$this->phoneNumberId) {
            $this->dispatch('notify', title: 'Error', message: 'No phone number configured', type: 'error');
            return;
        }

        $this->loadSettings();
        $this->loadStatistics();
        $this->initializeBusinessHours();
    }

    public function loadSettings()
    {
        $team = auth()->user()->currentTeam;

        $this->settings = CallSettings::firstOrCreate(
            [
                'team_id' => $team->id,
                'phone_number_id' => $this->phoneNumberId,
            ],
            [
                'calling_enabled' => false,
                'call_icon_visibility' => 'show',
                'callback_permission_enabled' => false,
                'business_hours' => $this->getDefaultBusinessHours(),
                'sip_config' => [],
            ]
        );

        $this->callingEnabled = $this->settings->calling_enabled;
        $this->callIconVisibility = $this->settings->call_icon_visibility;
        $this->callbackPermissionEnabled = $this->settings->callback_permission_enabled;
        $this->timezone = $this->settings->business_hours['timezone'] ?? 'UTC';
        $this->businessHours = $this->settings->business_hours['hours'] ?? [];
        $this->isRestricted = $this->settings->is_restricted;
        $this->restrictionReason = $this->settings->restriction_reason;

        // Load SIP config if exists
        $sipConfig = $this->settings->sip_config ?? [];
        $this->sipEnabled = !empty($sipConfig);
        $this->sipUri = $sipConfig['uri'] ?? '';
        $this->sipUsername = $sipConfig['username'] ?? '';
        $this->sipRealm = $sipConfig['realm'] ?? '';
        // Don't load password for security
    }

    public function loadStatistics()
    {
        $team = auth()->user()->currentTeam;

        $this->totalPermissions = CallPermission::where('team_id', $team->id)
            ->where('phone_number_id', $this->phoneNumberId)
            ->count();

        $this->activePermissions = CallPermission::where('team_id', $team->id)
            ->where('phone_number_id', $this->phoneNumberId)
            ->active()
            ->count();

        $this->expiredPermissions = CallPermission::where('team_id', $team->id)
            ->where('phone_number_id', $this->phoneNumberId)
            ->expired()
            ->count();

        $this->callsMadeToday = CallPermission::where('team_id', $team->id)
            ->where('phone_number_id', $this->phoneNumberId)
            ->whereDate('last_call_at', today())
            ->sum('calls_made_count');
    }

    public function initializeBusinessHours()
    {
        if (empty($this->businessHours)) {
            $this->businessHours = $this->getDefaultBusinessHours()['hours'];
        }
    }

    protected function getDefaultBusinessHours()
    {
        return [
            'timezone' => 'UTC',
            'hours' => [
                ['day' => 'MON', 'open' => '09:00', 'close' => '17:00', 'enabled' => true],
                ['day' => 'TUE', 'open' => '09:00', 'close' => '17:00', 'enabled' => true],
                ['day' => 'WED', 'open' => '09:00', 'close' => '17:00', 'enabled' => true],
                ['day' => 'THU', 'open' => '09:00', 'close' => '17:00', 'enabled' => true],
                ['day' => 'FRI', 'open' => '09:00', 'close' => '17:00', 'enabled' => true],
                ['day' => 'SAT', 'open' => '09:00', 'close' => '17:00', 'enabled' => false],
                ['day' => 'SUN', 'open' => '09:00', 'close' => '17:00', 'enabled' => false],
            ],
        ];
    }

    public function updateCallSettings()
    {
        $this->validate();

        $team = auth()->user()->currentTeam;

        try {
            // Prepare settings for Meta API
            $metaSettings = [
                'status' => $this->callingEnabled ? 'ENABLED' : 'DISABLED',
                'call_icon_visibility' => strtoupper($this->callIconVisibility) === 'SHOW' ? 'DEFAULT' : 'HIDDEN',
                'callback_permission_status' => $this->callbackPermissionEnabled ? 'ENABLED' : 'DISABLED',
            ];

            if ($this->syncWithBusinessHours) {
                $metaSettings['business_hours'] = $this->formatBusinessHoursForMeta();
                $metaSettings['timezone'] = $this->timezone;
            }

            // Update Meta's system settings
            $waService = new WhatsAppService($team);
            $response = $waService->updateSystemCallSettings($metaSettings);

            // Update local settings
            $businessHoursData = [
                'timezone' => $this->timezone,
                'hours' => $this->businessHours,
            ];

            $sipConfig = [];
            if ($this->sipEnabled) {
                $sipConfig = [
                    'uri' => $this->sipUri,
                    'username' => $this->sipUsername,
                    'realm' => $this->sipRealm,
                ];

                // Only update password if provided
                if (!empty($this->sipPassword)) {
                    $sipConfig['password'] = encrypt($this->sipPassword);
                }
            }

            $this->settings->update([
                'calling_enabled' => $this->callingEnabled,
                'call_icon_visibility' => $this->callIconVisibility,
                'callback_permission_enabled' => $this->callbackPermissionEnabled,
                'business_hours' => $businessHoursData,
                'sip_config' => $sipConfig,
            ]);

            if (isset($response['success']) && $response['success']) {
                $this->dispatch('notify', title: 'Success', message: 'Call settings updated successfully', type: 'success');
            } else {
                $msg = $response['message'] ?? ($response['error']['message'] ?? 'Unknown error');
                $this->dispatch('notify', title: 'Warning', message: 'Saved locally, but Meta sync failed: ' . $msg, type: 'warning');
            }

            $this->loadStatistics();

        } catch (\Exception $e) {
            Log::error('Failed to update call settings: ' . $e->getMessage());
            $this->dispatch('notify', title: 'Error', message: 'Failed to update settings: ' . $e->getMessage(), type: 'error');
        }
    }

    protected function formatBusinessHoursForMeta()
    {
        $formatted = [];
        foreach ($this->businessHours as $hour) {
            if ($hour['enabled'] ?? false) {
                $formatted[] = [
                    'day' => $hour['day'],
                    'open' => $hour['open'],
                    'close' => $hour['close'],
                ];
            }
        }
        return $formatted;
    }

    public function toggleDay($index)
    {
        $this->businessHours[$index]['enabled'] = !($this->businessHours[$index]['enabled'] ?? false);
    }

    public function applyToAll($index)
    {
        $template = $this->businessHours[$index];
        foreach ($this->businessHours as $key => $hour) {
            $this->businessHours[$key]['open'] = $template['open'];
            $this->businessHours[$key]['close'] = $template['close'];
        }
        $this->dispatch('notify', message: 'Hours applied to all days');
    }

    public function removeRestriction()
    {
        if (!$this->isRestricted) {
            return;
        }

        $this->settings->removeRestriction();
        $this->isRestricted = false;
        $this->restrictionReason = '';

        $this->dispatch('notify', title: 'Success', message: 'Restriction removed', type: 'success');
    }

    public function generateCallLink()
    {
        $team = auth()->user()->currentTeam;

        try {
            $waService = new WhatsAppService($team);
            $link = $waService->generateCallLink();

            $this->dispatch('call-link-generated', link: $link);
            $this->dispatch('notify', message: 'Call link generated');

        } catch (\Exception $e) {
            Log::error('Failed to generate call link: ' . $e->getMessage());
            $this->dispatch('notify', title: 'Error', message: 'Failed to generate link', type: 'error');
        }
    }

    public function getTimezonesProperty()
    {
        return \DateTimeZone::listIdentifiers();
    }

    public function revokePermission($id)
    {
        $permission = CallPermission::find($id);
        if ($permission) {
            $permission->revoke();
            $this->dispatch('notify', title: 'Success', message: 'Permission revoked', type: 'success');
            $this->loadStatistics();
        }
    }

    public function grantPermissionManually($id)
    {
        $permission = CallPermission::find($id);
        if ($permission) {
            $permission->grantPermission();
            $this->dispatch('notify', title: 'Success', message: 'Permission granted manually', type: 'success');
            $this->loadStatistics();
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $team = auth()->user()->currentTeam;

        $permissions = CallPermission::where('team_id', $team->id)
            ->where('phone_number_id', $this->phoneNumberId)
            ->with('contact')
            ->when($this->search, function ($query) {
                $query->whereHas('contact', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('phone_number', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('permission_status', $this->filterStatus);
            })
            ->latest()
            ->paginate(10);

        return view('livewire.whatsapp.call-settings-manager', [
            'permissions' => $permissions
        ]);
    }
}
