<?php

namespace App\Livewire\Campaigns;

use App\Jobs\ProcessCampaignJob;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\ContactTag;
use App\Models\WhatsappTemplate;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class Wizard extends Component
{
    use WithFileUploads;
    use \App\Traits\HasFlashMessages;

    public $step = 1;

    // Step 1: Details
    public $name;

    public $campaignType = 'broadcast'; // 'broadcast' or 'drip'

    public $scheduled_at;

    public $scheduleMode = 'now'; // 'now' or 'later'

    // Step 2: Audience
    public $audienceType = 'tags'; // 'tags', 'contacts', or 'all'

    public $selectedTags = [];

    public $selectedContacts = [];

    public $audienceCount = 0;

    // Step 3: Message
    public $selectedTemplateId;

    public $templateVars = []; // ['{{1}}' => 'value']

    public $headerMediaFile; // For IMAGE/VIDEO/DOCUMENT headers (Upload)

    public $headerMediaUrl; // For IMAGE/VIDEO/DOCUMENT headers (URL fallback)

    public $headerTextVar; // For TEXT header variable

    // Drip Specific
    public $dripSteps = []; // [['template_id' => null, 'delay_minutes' => 0, 'variables' => [], 'template_name' => '', 'language' => 'en_US']]

    public $currentDripStep = 0;

    // Step 1: Retry Configuration
    public $retry_enabled = false;

    public $max_retries = 3;

    public $retry_interval = 60; // seconds

    public $retry_strategy = 'exponential';

    // UI Helpers
    public $isUploading = false;

    public function getStepsProperty()
    {
        return [
            1 => 'Setup',
            2 => 'Audience',
            3 => 'Message',
            4 => 'Review',
        ];
    }

    public $editingCampaignId;

    public function mount($campaignId = null)
    {
        $this->name = session('default_name', request('default_name', 'Campaign '.date('Y-m-d H:i')));
        $this->scheduled_at = now()->addMinutes(5)->format('Y-m-d\TH:i');

        if ($campaignId) {
            $campaign = Campaign::where('team_id', \Illuminate\Support\Facades\Auth::user()->currentTeam->id)->findOrFail($campaignId);
            $this->editingCampaignId = $campaign->id;
            $this->name = $campaign->name;
            $this->campaignType = $campaign->campaign_type;
            $this->selectedTemplateId = $campaign->template_id;
            $this->templateVars = $campaign->template_variables ?? [];
            $this->audienceType = $campaign->audience_filters['type'] ?? 'tags';
            $this->selectedTags = $campaign->audience_filters['tags'] ?? [];
            $this->selectedContacts = $campaign->audience_filters['contacts'] ?? [];
            $this->retry_enabled = $campaign->retry_config['enabled'] ?? false;
            $this->max_retries = $campaign->retry_config['max_retries'] ?? 3;
            $this->retry_interval = $campaign->retry_config['retry_interval'] ?? 60;
            $this->retry_strategy = $campaign->retry_config['retry_strategy'] ?? 'exponential';
            
            if ($this->campaignType === 'drip') {
                $steps = $campaign->steps ?? [];
                if (count($steps) > 0) {
                    // Skip the first step as it is the main campaign template/message
                    $this->dripSteps = array_slice($steps, 1);
                }
            }

            $this->calculateAudience();
        }

        if (session()->has('retarget_ids') || request()->has('retarget_ids')) {
            $this->audienceType = 'contacts';
            $this->selectedContacts = session('retarget_ids', request('retarget_ids'));
            $this->calculateAudience();
        }

        // Initialize retry defaults if not editing
        if (!$campaignId) {
            $this->retry_enabled = false;
            $this->max_retries = 3;
            $this->retry_interval = 60;
            $this->retry_strategy = 'exponential';
        }
    }

    #[Computed]
    public function templates()
    {
        return WhatsappTemplate::where('team_id', \Illuminate\Support\Facades\Auth::user()->currentTeam->id)
            ->where('status', 'APPROVED')
            ->get();
    }

    #[Computed]
    public function tags()
    {
        return ContactTag::where('team_id', \Illuminate\Support\Facades\Auth::user()->currentTeam->id)->get();
    }

    public $contactSearch = '';
    
    #[Computed]
    public function contacts()
    {
        $query = Contact::where('team_id', \Illuminate\Support\Facades\Auth::user()->currentTeam->id);
        
        if ($this->contactSearch) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->contactSearch . '%')
                  ->orWhere('phone_number', 'like', '%' . $this->contactSearch . '%');
            });
        }

        return $query->orderBy('name')
            ->take(50)
            ->get();
    }

    public function updatedAudienceType()
    {
        $this->calculateAudience();
    }

    public function updatedSelectedTags()
    {
        $this->calculateAudience();
    }

    #[On('audienceUpdated')]
    public function updateAudience($selectedTags = null, $selectedContacts = null)
    {
        if ($selectedTags !== null) $this->selectedTags = $selectedTags;
        if ($selectedContacts !== null) $this->selectedContacts = $selectedContacts;
        $this->calculateAudience();
    }

    #[On('audienceTypeUpdated')]
    public function updateAudienceType($type)
    {
        $this->audienceType = $type;
        $this->calculateAudience();
    }

    public function calculateAudience()
    {
        $query = Contact::where('team_id', \Illuminate\Support\Facades\Auth::user()->currentTeam->id);

        if ($this->audienceType === 'tags' && ! empty($this->selectedTags)) {
            $query->whereHas('tags', function ($q) {
                $q->whereIn('contact_tags.id', $this->selectedTags);
            });
        } elseif ($this->audienceType === 'contacts' && ! empty($this->selectedContacts)) {
            $query->whereIn('id', $this->selectedContacts);
        } elseif ($this->audienceType === 'all') {
            // Keep all
        } else {
            // No selection
            if ($this->audienceType !== 'all') {
                $this->audienceCount = 0;

                return;
            }
        }

        $this->audienceCount = $query->count();
    }

    public function addDripStep()
    {
        $this->dripSteps[] = [
            'template_id' => null,
            'template_name' => '',
            'language' => 'en_US',
            'delay_minutes' => 1440, // 1 day
            'variables' => [],
            'header_params' => [],
        ];
        $this->currentDripStep = count($this->dripSteps) - 1;
    }

    public function removeDripStep($index)
    {
        unset($this->dripSteps[$index]);
        $this->dripSteps = array_values($this->dripSteps);
        $this->currentDripStep = max(0, count($this->dripSteps) - 1);
    }

    public function selectDripStep($index)
    {
        $this->currentDripStep = $index;

        if ($index === 0) {
            // Main Campaign Template
            // (Props already in $this->selectedTemplateId etc.)
            // But we might need to reset them if they were changed in a follow-up
            return;
        }

        $step = $this->dripSteps[$index - 1];
        $this->selectedTemplateId = $step['template_id'];
        $this->templateVars = $step['variables'];
    }

    public function updatedTemplateVars($value, $key)
    {
        if ($this->campaignType === 'drip' && $this->currentDripStep > 0) {
            $this->dripSteps[$this->currentDripStep - 1]['variables'] = $this->templateVars;
        }
    }

    public function updatedSelectedTemplateId($value)
    {
        $this->templateVars = [];
        $this->headerMediaFile = null;
        $this->headerMediaUrl = null;
        $this->headerTextVar = null;

        if ($value) {
            $template = WhatsappTemplate::where('team_id', \Illuminate\Support\Facades\Auth::user()->currentTeam->id)->find($value);
            if ($template) {
                if ($this->campaignType === 'drip' && $this->currentDripStep > 0) {
                    $this->dripSteps[$this->currentDripStep - 1]['template_id'] = $template->id;
                    $this->dripSteps[$this->currentDripStep - 1]['template_name'] = $template->name;
                    $this->dripSteps[$this->currentDripStep - 1]['language'] = $template->language;
                }

                // Initialize variables based on body params count
                $bodyText = '';
                foreach ($template->components ?? [] as $c) {
                    if (($c['type'] ?? '') === 'BODY') {
                        $bodyText = $c['text'] ?? '';
                    }
                }
                preg_match_all('/{{(\d+)}}/', $bodyText, $matches);
                $paramCount = count(array_unique($matches[1] ?? []));

                for ($i = 1; $i <= $paramCount; $i++) {
                    $this->templateVars[$i - 1] = '';
                }

                if ($this->campaignType === 'drip' && isset($this->dripSteps[$this->currentDripStep])) {
                    $this->dripSteps[$this->currentDripStep]['variables'] = $this->templateVars;
                }
            }
        }
    }

    public function saveDraft()
    {
        $this->validate(['name' => 'required|min:3']);

        $campaignData = [
            'team_id' => \Illuminate\Support\Facades\Auth::user()->currentTeam->id,
            'name' => $this->name,
            'campaign_name' => $this->name,
            'campaign_type' => $this->campaignType,
            'template_id' => $this->selectedTemplateId,
            'template_language' => $this->selectedTemplateId ? WhatsappTemplate::find($this->selectedTemplateId)?->language : 'en_US',
            'template_variables' => array_values($this->templateVars),
            'steps' => $this->campaignType === 'drip' ? $this->dripSteps : null,
            'audience_filters' => [
                'type' => $this->audienceType,
                'tags' => $this->selectedTags,
                'contacts' => $this->selectedContacts,
                'all' => $this->audienceType === 'all',
            ],
            'status' => 'draft',
            'retry_config' => [
                'enabled' => (bool) $this->retry_enabled,
                'max_retries' => (int) $this->max_retries,
                'retry_interval' => (int) $this->retry_interval,
                'retry_strategy' => $this->retry_strategy,
            ],
        ];

        if ($this->editingCampaignId) {
            $campaign = Campaign::where('team_id', \Illuminate\Support\Facades\Auth::user()->currentTeam->id)->findOrFail($this->editingCampaignId);
            $campaign->update($campaignData);
        } else {
            $campaign = Campaign::create($campaignData);
        }

        $this->flash('Campaign saved as draft!', 'success');
        return redirect()->route('campaigns.index');
    }

    public function launch()
    {
        $this->validate([
            'name' => 'required|min:3',
            'selectedTemplateId' => 'required',
            'audienceCount' => 'numeric|min:1',
            'scheduled_at' => $this->scheduleMode === 'later' ? 'required|date|after:now' : 'nullable',
        ]);

        if ($this->scheduleMode === 'now') {
            $this->scheduled_at = now();
        }

        $template = WhatsappTemplate::where('team_id', \Illuminate\Support\Facades\Auth::user()->currentTeam->id)->findOrFail($this->selectedTemplateId);

        // Handle Media Header
        $finalHeaderMedia = null;
        if ($this->headerMediaFile) {
            $finalHeaderMedia = $this->headerMediaFile->store('campaigns/headers', 'public');
            $finalHeaderMedia = asset('storage/'.$finalHeaderMedia);
        } elseif ($this->headerMediaUrl) {
            $finalHeaderMedia = $this->headerMediaUrl;
        }

        // Prepare variables
        $finalVars = array_values($this->templateVars);

        // If there's a header media, it usually goes as the first variable in some implementations,
        // but let's check how the backend expects it.
        // Based on previous code: if (!empty($this->headerMediaUrl)) { array_unshift($finalVars, $this->headerMediaUrl); }
        if ($finalHeaderMedia) {
            // In some cases, we might want to store the media path separately in the DB
            // but for current ProcessCampaignJob logic, we'll stick to prepending.
            // array_unshift($finalVars, $finalHeaderMedia);
        }

        $campaignData = [
            'team_id' => \Illuminate\Support\Facades\Auth::user()->currentTeam->id,
            'name' => $this->name,
            'campaign_name' => $this->name,
            'campaign_type' => $this->campaignType,
            'template_id' => $template->id,
            'template_name' => $template->name,
            'template_language' => $template->language,
            'template_variables' => $finalVars,
            'steps' => $this->campaignType === 'drip' ? array_merge([
                [
                    'template_id' => $template->id,
                    'template_name' => $template->name,
                    'language' => $template->language,
                    'variables' => $finalVars,
                    'header_params' => $finalHeaderMedia ? [$finalHeaderMedia] : ($this->headerTextVar ? [$this->headerTextVar] : []),
                    'delay_minutes' => 0,
                ],
            ], $this->dripSteps) : null,
            'header_params' => $finalHeaderMedia ? [$finalHeaderMedia] : ($this->headerTextVar ? [$this->headerTextVar] : []),
            'audience_filters' => [
                'type' => $this->audienceType,
                'tags' => $this->selectedTags,
                'contacts' => $this->selectedContacts,
                'all' => $this->audienceType === 'all',
            ],
            'scheduled_at' => $this->scheduled_at,
            'status' => 'scheduled',
            'filename' => $finalHeaderMedia, // Store file path for reference
            'retry_config' => [
                'enabled' => (bool) $this->retry_enabled,
                'max_retries' => (int) $this->max_retries,
                'retry_interval' => (int) $this->retry_interval,
                'retry_strategy' => $this->retry_strategy,
            ],
        ];

        if ($this->editingCampaignId) {
            $campaign = Campaign::where('team_id', \Illuminate\Support\Facades\Auth::user()->currentTeam->id)->findOrFail($this->editingCampaignId);
            $campaign->update($campaignData);
        } else {
            $campaign = Campaign::create($campaignData);
        }

        $delay = Carbon::parse($this->scheduled_at);
        $seconds = now()->diffInSeconds($delay, false);
        $delaySeconds = $seconds > 0 ? $seconds : 0;

        $criteria = [
            'selection_type' => $this->audienceType,
            'tags' => $this->selectedTags,
            'ids' => $this->selectedContacts,
        ];

        \App\Jobs\PrepareCampaignJob::dispatch($campaign->id, $criteria)->delay($delaySeconds);

        $this->flash('Campaign Launched Successfully!', 'success');

        return redirect()->route('campaigns.index');
    }

    #[Computed]
    public function templateInfo()
    {
        if (! $this->selectedTemplateId) {
            return null;
        }

        $template = WhatsappTemplate::where('team_id', \Illuminate\Support\Facades\Auth::user()->currentTeam->id)->find($this->selectedTemplateId);
        if (! $template) {
            return null;
        }

        $components = $template->components ?? [];
        $info = [
            'headerType' => 'NONE',
            'headerText' => '',
            'bodyText' => '',
            'footerText' => '',
            'paramCount' => 0,
        ];

        foreach ($components as $c) {
            if (($c['type'] ?? '') === 'HEADER') {
                $info['headerType'] = $c['format'] ?? 'TEXT';
                if ($info['headerType'] === 'TEXT') {
                    $info['headerText'] = $c['text'] ?? '';
                }
            }
            if (($c['type'] ?? '') === 'BODY') {
                $info['bodyText'] = $c['text'] ?? '';
            }
            if (($c['type'] ?? '') === 'FOOTER') {
                $info['footerText'] = $c['text'] ?? '';
            }
        }

        preg_match_all('/{{(\d+)}}/', $info['bodyText'], $matches);
        $info['paramCount'] = count(array_unique($matches[1] ?? []));

        return $info;
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.campaigns.wizard');
    }
}
