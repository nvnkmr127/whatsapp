<?php

namespace App\Livewire\Settings;

use App\Models\OnboardingStatus;
use App\Models\WhatsappTemplate;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Title('Onboarding Automation Settings')]
class OnboardingAutomation extends Component
{
    public $remoteReminderTemplate;
    public $remoteReminderVariables = '{name}, {setup_link}';

    public $profileReminderTemplate;
    public $profileReminderVariables = '{name}, {setup_link}';

    public $campaignTutorialTemplate;
    public $campaignTutorialVariables = '{name}';

    // Delays (in hours)
    public $firstDelay = 1;
    public $secondDelay = 2;
    public $thirdDelay = 24;

    // Channels
    public $onboardingChannel = 'whatsapp'; // whatsapp, email, both

    public $templates = [];
    public $availablePlaceholders = [
        '{name}' => 'User Full Name',
        '{setup_link}' => 'Onboarding/Setup URL',
        '{email}' => 'User Email Address',
        '{company}' => 'Team/Company Name'
    ];

    #[Layout('layouts.app')]
    public function render()
    {
        $stats = [
            'signups' => OnboardingStatus::count(),
            'completed' => OnboardingStatus::where('onboarding_completed', true)->count(),
            'reminders_sent' => OnboardingStatus::sum('reminders_sent_count'),
            'recovered' => OnboardingStatus::where('was_recovered', true)->count(),
        ];

        $stats['lost'] = max(0, $stats['signups'] - $stats['completed']);

        // Get previews for selected templates
        $previews = [
            'remote' => $this->getTemplatePreview($this->remoteReminderTemplate),
            'profile' => $this->getTemplatePreview($this->profileReminderTemplate),
            'campaign' => $this->getTemplatePreview($this->campaignTutorialTemplate),
        ];

        return view('livewire.settings.onboarding-automation', [
            'stats' => $stats,
            'previews' => $previews
        ]);
    }

    protected function getTemplatePreview($name)
    {
        if (!$name)
            return null;

        $template = collect($this->templates)->firstWhere('name', $name);
        if (!$template)
            return null;

        $content = [];
        foreach ($template['components'] ?? [] as $component) {
            if (isset($component['text'])) {
                $content[] = $component['text'];
            }
        }
        return implode("\n", $content);
    }

    public function mount()
    {
        if (!Auth::user()->is_super_admin) {
            abort(403);
        }

        \Illuminate\Support\Facades\Gate::authorize('manage-settings');

        // Load all approved templates with components for preview
        $this->templates = WhatsappTemplate::where('status', 'APPROVED')
            ->orderBy('name')
            ->get(['id', 'name', 'components'])
            ->toArray();

        // Load current settings
        $this->remoteReminderTemplate = get_setting('onboarding_remote_reminder_template');
        $this->remoteReminderVariables = get_setting('onboarding_remote_reminder_variables', '{name}, {setup_link}');

        $this->profileReminderTemplate = get_setting('onboarding_profile_reminder_template');
        $this->profileReminderVariables = get_setting('onboarding_profile_reminder_variables', '{name}, {setup_link}');

        $this->campaignTutorialTemplate = get_setting('onboarding_campaign_tutorial_template');
        $this->campaignTutorialVariables = get_setting('onboarding_campaign_tutorial_variables', '{name}');

        $this->firstDelay = (int) get_setting('onboarding_first_delay', 1);
        $this->secondDelay = (int) get_setting('onboarding_second_delay', 2);
        $this->thirdDelay = (int) get_setting('onboarding_third_delay', 24);
        $this->onboardingChannel = get_setting('onboarding_channel', 'whatsapp');
    }

    public function addPlaceholder($field, $placeholder)
    {
        $current = $this->$field;
        if (empty($current)) {
            $this->$field = $placeholder;
        } else {
            $this->$field = $current . ', ' . $placeholder;
        }
    }

    public function save()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-settings');

        set_setting('onboarding_remote_reminder_template', $this->remoteReminderTemplate, 'onboarding');
        set_setting('onboarding_remote_reminder_variables', $this->remoteReminderVariables, 'onboarding');

        set_setting('onboarding_profile_reminder_template', $this->profileReminderTemplate, 'onboarding');
        set_setting('onboarding_profile_reminder_variables', $this->profileReminderVariables, 'onboarding');

        set_setting('onboarding_campaign_tutorial_template', $this->campaignTutorialTemplate, 'onboarding');
        set_setting('onboarding_campaign_tutorial_variables', $this->campaignTutorialVariables, 'onboarding');

        set_setting('onboarding_first_delay', $this->firstDelay, 'onboarding');
        set_setting('onboarding_second_delay', $this->secondDelay, 'onboarding');
        set_setting('onboarding_third_delay', $this->thirdDelay, 'onboarding');
        set_setting('onboarding_channel', $this->onboardingChannel, 'onboarding');

        $this->dispatch('notify', type: 'success', message: 'Onboarding automation settings saved successfully!');
        audit('settings.onboarding_updated', "Onboarding automation templates updated by " . Auth::user()->name);
    }
}
