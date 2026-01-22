<?php

namespace App\Livewire\Teams;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class InboxSettings extends Component
{

    public $readReceiptsEnabled = true;
    public $welcomeMessageEnabled = false;
    public $welcomeMessage = '';
    public $offHoursMessageEnabled = false;
    public $offHoursMessage = '';
    public $aiAutoReplyEnabled = false;

    // Working Hours: Array of [open, close] or null if closed
    public $workingHours = [];
    public $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    // Modal Config
    public $configModalOpen = false;
    public $editingType = null; // 'welcome' or 'off-hours'

    // Config State
    public $configMsgType = 'regular'; // 'regular' or 'template'
    public $regularType = 'text'; // 'text', 'image', 'video', 'audio', 'document'
    public $regularContent = '';
    public $regularMediaUrl = '';
    public $regularCaption = '';
    public $templateName = '';
    public $templateLanguage = 'en_US';
    public $availableTemplates = [];

    public function mount()
    {
        $team = Auth::user()->currentTeam;

        $this->readReceiptsEnabled = $team->read_receipts_enabled;
        $this->welcomeMessageEnabled = $team->welcome_message_enabled;
        $this->welcomeMessage = $team->welcome_message;

        // Mapped to existing 'away_message'
        $this->offHoursMessageEnabled = $team->away_message_enabled;
        $this->offHoursMessage = $team->away_message;

        $this->aiAutoReplyEnabled = $team->ai_auto_reply_enabled;

        // Initialize working hours
        $dbHours = $team->business_hours ?? [];
        foreach ($this->days as $day) {
            if (isset($dbHours[$day])) {
                $this->workingHours[$day] = [
                    'enabled' => true,
                    'open' => $dbHours[$day][0],
                    'close' => $dbHours[$day][1],
                ];
            } else {
                $this->workingHours[$day] = [
                    'enabled' => false,
                    'open' => '09:00',
                    'close' => '17:00',
                ];
            }
        }
    }

    public function openConfig($type)
    {
        $this->editingType = $type;
        $team = Auth::user()->currentTeam;

        // Load existing config
        $config = $type === 'welcome'
            ? ($team->welcome_message_config ?? [])
            : ($team->away_message_config ?? []);

        // Fallback to simpler legacy fields if config is empty
        if (empty($config)) {
            $legacyContent = $type === 'welcome' ? $team->welcome_message : $team->away_message;
            $this->configMsgType = 'regular';
            $this->regularType = 'text';
            $this->regularContent = $legacyContent;
            $this->regularMediaUrl = '';
            $this->regularCaption = '';
            $this->templateName = '';
        } else {
            $this->configMsgType = $config['type'] ?? 'regular';
            if ($this->configMsgType === 'regular') {
                $this->regularType = $config['regular_type'] ?? 'text';
                $this->regularContent = $config['text'] ?? '';
                $this->regularMediaUrl = $config['media_url'] ?? '';
                $this->regularCaption = $config['caption'] ?? '';
            } else {
                $this->templateName = $config['template_name'] ?? '';
                $this->templateLanguage = $config['language'] ?? 'en_US';
            }
        }

        // Fetch templates if not already loaded (and if we have credentials)
        if (empty($this->availableTemplates)) {
            try {
                $service = new \App\Services\WhatsAppService();
                $service->setTeam($team);
                $response = $service->getTemplates();

                if ($response['success'] && isset($response['data']['data'])) {
                    // Filter for APPROVED templates only?
                    $this->availableTemplates = collect($response['data']['data'])
                        ->where('status', 'APPROVED')
                        ->map(function ($tpl) {
                            return [
                                'name' => $tpl['name'],
                                'language' => $tpl['language'],
                                'category' => $tpl['category']
                            ];
                        })->values()->toArray();
                }
            } catch (\Exception $e) {
                // Fail silently or log?
                // Log::error("Failed to fetch templates: " . $e->getMessage());
            }
        }

        $this->configModalOpen = true;
    }

    public function saveConfig()
    {
        $team = Auth::user()->currentTeam;

        $newConfig = [
            'type' => $this->configMsgType
        ];

        $summaryText = '';

        if ($this->configMsgType === 'regular') {
            $newConfig['regular_type'] = $this->regularType;
            if ($this->regularType === 'text') {
                $newConfig['text'] = $this->regularContent;
                $summaryText = $this->regularContent;
            } else {
                $newConfig['media_url'] = $this->regularMediaUrl;
                $newConfig['caption'] = $this->regularCaption;
                $summaryText = "[" . strtoupper($this->regularType) . "] " . $this->regularCaption;
            }
        } else {
            $newConfig['template_name'] = $this->templateName;
            $newConfig['language'] = $this->templateLanguage;
            $summaryText = "[TEMPLATE] " . $this->templateName;
        }

        $data = [];
        if ($this->editingType === 'welcome') {
            $data['welcome_message_config'] = $newConfig;
            $data['welcome_message'] = $summaryText; // Sync legacy
            $this->welcomeMessage = $summaryText; // Update UI view
        } else {
            $data['away_message_config'] = $newConfig;
            $data['away_message'] = $summaryText;
            $this->offHoursMessage = $summaryText;
        }

        $team->forceFill($data)->save();

        $this->configModalOpen = false;
        $this->dispatch('saved-config');
    }


    public function save()
    {
        $team = Auth::user()->currentTeam;

        $businessHours = [];
        foreach ($this->days as $day) {
            if ($this->workingHours[$day]['enabled']) {
                $businessHours[$day] = [
                    $this->workingHours[$day]['open'],
                    $this->workingHours[$day]['close']
                ];
            }
        }

        $team->forceFill([
            'read_receipts_enabled' => $this->readReceiptsEnabled,
            'welcome_message_enabled' => $this->welcomeMessageEnabled,
            'welcome_message' => $this->welcomeMessage,
            'away_message_enabled' => $this->offHoursMessageEnabled,
            'away_message' => $this->offHoursMessage,
            'ai_auto_reply_enabled' => $this->aiAutoReplyEnabled,
            'business_hours' => $businessHours,
        ])->save();

        session()->flash('message', 'Inbox settings saved successfully.');
        $this->dispatch('saved');
    }

    public function render()
    {
        return view('livewire.teams.inbox-settings');
    }
}
