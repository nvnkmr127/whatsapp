<?php

namespace App\Livewire\Campaigns;

use App\Models\Contact;
use App\Models\ContactTag;
use App\Models\WhatsappTemplate;
use App\Models\Campaign;
use App\Jobs\ProcessCampaignJob;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Carbon\Carbon;

class Wizard extends Component
{
    use WithFileUploads;

    public $step = 1;

    // Step 1: Details
    public $name;
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

    public function mount()
    {
        $this->name = 'Campaign ' . date('Y-m-d H:i');
        $this->scheduled_at = now()->addMinutes(5)->format('Y-m-d\TH:i');
    }

    #[Computed]
    public function templates()
    {
        return WhatsappTemplate::where('team_id', auth()->user()->currentTeam->id)
            ->where('status', 'APPROVED')
            ->get();
    }

    #[Computed]
    public function tags()
    {
        return ContactTag::where('team_id', auth()->user()->currentTeam->id)->get();
    }

    #[Computed]
    public function contacts()
    {
        return Contact::where('team_id', auth()->user()->currentTeam->id)
            ->orderBy('name')
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

    public function updatedSelectedContacts()
    {
        $this->calculateAudience();
    }

    public function calculateAudience()
    {
        $query = Contact::where('team_id', auth()->user()->currentTeam->id);

        if ($this->audienceType === 'tags' && !empty($this->selectedTags)) {
            $query->whereHas('tags', function ($q) {
                $q->whereIn('contact_tags.id', $this->selectedTags);
            });
        } elseif ($this->audienceType === 'contacts' && !empty($this->selectedContacts)) {
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

    public function updatedSelectedTemplateId($value)
    {
        $this->templateVars = [];
        $this->headerMediaFile = null;
        $this->headerMediaUrl = null;
        $this->headerTextVar = null;

        if ($value) {
            $template = WhatsappTemplate::where('team_id', auth()->user()->currentTeam->id)->find($value);
            if ($template) {
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
            }
        }
    }

    public function launch()
    {
        $this->validate([
            'name' => 'required|min:3',
            'selectedTemplateId' => 'required',
            'audienceCount' => 'numeric|min:1',
            'scheduled_at' => $this->scheduleMode === 'later' ? 'required|date|after:now' : 'nullable'
        ]);

        if ($this->scheduleMode === 'now') {
            $this->scheduled_at = now();
        }

        $template = WhatsappTemplate::where('team_id', auth()->user()->currentTeam->id)->findOrFail($this->selectedTemplateId);

        // Handle Media Header
        $finalHeaderMedia = null;
        if ($this->headerMediaFile) {
            $finalHeaderMedia = $this->headerMediaFile->store('campaigns/headers', 'public');
            $finalHeaderMedia = asset('storage/' . $finalHeaderMedia);
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

        $campaign = Campaign::create([
            'team_id' => auth()->user()->currentTeam->id,
            'name' => $this->name,
            'campaign_name' => $this->name,
            'template_id' => $template->id,
            'template_name' => $template->name,
            'template_language' => $template->language,
            'template_variables' => $finalVars,
            'header_params' => $finalHeaderMedia ? [$finalHeaderMedia] : ($this->headerTextVar ? [$this->headerTextVar] : []),
            'audience_filters' => [
                'type' => $this->audienceType,
                'tags' => $this->selectedTags,
                'contacts' => $this->selectedContacts,
                'all' => $this->audienceType === 'all'
            ],
            'scheduled_at' => $this->scheduled_at,
            'status' => 'scheduled',
            'filename' => $finalHeaderMedia // Store file path for reference
        ]);

        $delay = Carbon::parse($this->scheduled_at);
        $seconds = now()->diffInSeconds($delay, false);
        $delaySeconds = $seconds > 0 ? $seconds : 0;

        ProcessCampaignJob::dispatch($campaign->id)->delay($delaySeconds);

        session()->flash('success', 'Campaign Launched Successfully!');
        return redirect()->route('campaigns.index');
    }

    #[Computed]
    public function templateInfo()
    {
        if (!$this->selectedTemplateId) {
            return null;
        }

        $template = WhatsappTemplate::where('team_id', auth()->user()->currentTeam->id)->find($this->selectedTemplateId);
        if (!$template) {
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

