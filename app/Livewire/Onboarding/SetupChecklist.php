<?php

namespace App\Livewire\Onboarding;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class SetupChecklist extends Component
{
    public $steps = [];
    public $isComplete = false;
    public $showChecklist = true;
    public $diagnosticResult = null;
    public $isDiagnosing = false;
    public $isSyncing = false;
    public $isSendingTest = false;

    use \App\Traits\WhatsApp;

    public function mount()
    {
        $this->refreshSteps();
    }

    public function refreshSteps()
    {
        $team = Auth::user()->currentTeam;

        if (!$team) {
            $this->showChecklist = false;
            return;
        }

        $previousSteps = $this->steps;

        $this->steps = [
            [
                'id' => 'connect_account',
                'title' => 'Connect Facebook',
                'description' => 'Link your Meta Business account.',
                'completed' => !empty($team->whatsapp_access_token),
                'link' => route('teams.whatsapp_config'),
                'icon' => 'fb'
            ],
            [
                'id' => 'waba_id',
                'title' => 'Detect AI Account',
                'description' => 'Find your Business Account ID.',
                'completed' => !empty($team->whatsapp_business_account_id),
                'link' => route('teams.whatsapp_config'),
                'icon' => 'waba'
            ],
            [
                'id' => 'phone_number',
                'title' => 'Verify Number',
                'description' => 'Register your phone identity.',
                'completed' => !empty($team->whatsapp_phone_number_id),
                'link' => route('teams.whatsapp_config'),
                'icon' => 'phone'
            ],
            [
                'id' => 'active_status',
                'title' => 'Ready for Launch',
                'description' => 'Account is verified and active.',
                'completed' => $team->whatsapp_connected || $team->isWhatsAppActive(),
                'link' => route('teams.whatsapp_config'),
                'icon' => 'rocket'
            ],
        ];

        // Check for new completions to trigger celebration
        if (!empty($previousSteps)) {
            foreach ($this->steps as $index => $step) {
                if ($step['completed'] && !($previousSteps[$index]['completed'] ?? false)) {
                    $this->dispatch('onboarding-milestone-completed', id: $step['id']);
                }
            }
        }

        $completedCount = collect($this->steps)->where('completed', true)->count();
        $this->isComplete = $completedCount === count($this->steps);

        // Hide if complete for more than 24 hours? 
        // For now, let's just allow manual dismissal or hide when 100% complete.
        if ($this->isComplete) {
            // $this->showChecklist = false;
        }
    }

    public function toggleChecklist()
    {
        $this->showChecklist = !$this->showChecklist;
    }

    public function diagnoseConnection()
    {
        $this->isDiagnosing = true;
        $this->diagnosticResult = null;

        $team = Auth::user()->currentTeam;

        if (!$team || !$team->whatsapp_access_token) {
            $this->diagnosticResult = [
                'status' => 'error',
                'message' => 'No access token found. Please connect your Facebook account first.'
            ];
            $this->isDiagnosing = false;
            return;
        }

        try {
            // 1. Check Token Validity & Scopes
            $debug = $this->debugToken($team->whatsapp_access_token);

            if (!$debug['status']) {
                $this->diagnosticResult = [
                    'status' => 'error',
                    'message' => 'Token Invalid: ' . ($debug['message'] ?? 'Unknown error')
                ];
            } else {
                $scopes = collect($debug['data']['granular_scopes'] ?? [])->pluck('scope')->toArray();
                $required = ['whatsapp_business_messaging', 'whatsapp_business_management'];
                $missing = array_diff($required, $scopes);

                if (!empty($missing)) {
                    $this->diagnosticResult = [
                        'status' => 'warning',
                        'message' => 'Missing permissions: ' . implode(', ', $missing) . '. Please reconnect and grant all requested permissions.'
                    ];
                } else {
                    // 2. Check Webhook Subscription if WABA exists
                    if ($team->whatsapp_business_account_id) {
                        $hook = $this->checkWebhookSubscription($team->whatsapp_business_account_id, $team->whatsapp_access_token);
                        if (!$hook['status'] || !$hook['is_subscribed']) {
                            $this->diagnosticResult = [
                                'status' => 'warning',
                                'message' => 'Connection is strong, but webhooks are not subscribed. Click "Sync" to fix.'
                            ];
                        } else {
                            $this->diagnosticResult = [
                                'status' => 'success',
                                'message' => 'Connection is healthy! All permissions and webhooks are active.'
                            ];
                        }
                    } else {
                        $this->diagnosticResult = [
                            'status' => 'success',
                            'message' => 'Token is valid. Now, select your Business Account in settings.'
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            $this->diagnosticResult = [
                'status' => 'error',
                'message' => 'Diagnostic failed: ' . $e->getMessage()
            ];
        }

        $this->isDiagnosing = false;
        $this->refreshSteps();
    }

    public function syncNow()
    {
        $this->isSyncing = true;
        $team = Auth::user()->currentTeam;

        try {
            if ($team->whatsapp_business_account_id && $team->whatsapp_access_token) {
                // Subscribe to webhooks first
                $this->subscribeToWebhooks($team->whatsapp_business_account_id, $team->whatsapp_access_token);

                // Then sync templates
                $result = $this->loadTemplatesFromWhatsApp();

                if ($result['status']) {
                    session()->flash('checklist_message', 'Sync completed! Found ' . ($result['count'] ?? 0) . ' templates.');
                } else {
                    session()->flash('checklist_error', 'Sync failed: ' . $result['message']);
                }
            } else {
                session()->flash('checklist_error', 'Cannot sync: WhatsApp not fully configured.');
            }
        } catch (\Exception $e) {
            session()->flash('checklist_error', 'Sync error: ' . $e->getMessage());
        }

        $this->isSyncing = false;
        $this->refreshSteps();
    }

    public function sendTestMessage()
    {
        $this->isSendingTest = true;
        $team = Auth::user()->currentTeam;
        $user = Auth::user();

        try {
            if (!$team->isWhatsAppActive()) {
                session()->flash('checklist_error', 'Cannot send test message: WhatsApp is not fully configured or connected.');
                $this->isSendingTest = false;
                return;
            }

            if (!$user->phone_number) {
                session()->flash('checklist_error', 'Your profile is missing a phone number. Please add one in your settings first.');
                $this->isSendingTest = false;
                return;
            }

            $service = new \App\Services\WhatsAppService($team);
            $message = "Hello from Antigravity! 🚀 Your WhatsApp Business API integration is officially alive. (Test ID: " . uniqid() . ")";

            $service->sendText($user->phone_number, $message);

            session()->flash('checklist_message', 'Test message sent successfully to ' . $user->phone_number . '! Check your WhatsApp.');
        } catch (\Exception $e) {
            session()->flash('checklist_error', 'Test failed: ' . $e->getMessage());
        }

        $this->isSendingTest = false;
        $this->refreshSteps();
    }

    public function render()
    {
        return view('livewire.onboarding.setup-checklist');
    }
}
