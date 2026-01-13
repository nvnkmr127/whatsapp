<?php

namespace App\Livewire\Campaigns;

use Livewire\Component;
use Livewire\Attributes\Layout;

class Wizard extends Component
{
    public $step = 1;

    // Step 1: Details
    public $name;
    public $scheduled_at;
    public $scheduleMode = 'now'; // 'now' or 'later'

    // Step 2: Audience
    public $audienceType = 'tags'; // 'all' or 'tags'
    public $selectedTags = [];
    public $audienceCount = 0;

    // Step 3: Message
    public $selectedTemplateId;
    public $templateVars = []; // ['{{1}}' => 'value'] or simple index array
    public $headerMediaUrl; // For IMAGE/VIDEO/DOCUMENT headers

    public function getStepsProperty()
    {
        return [
            1 => 'Configuration',
            2 => 'Target Audience',
            3 => 'Mission Message',
            4 => 'Final Review',
        ];
    }

    public function mount()
    {
        $this->name = 'Campaign ' . date('Y-m-d H:i');
        $this->scheduled_at = now()->addMinutes(5)->format('Y-m-d\TH:i');
    }

    public function getTemplatesProperty()
    {
        return \App\Models\WhatsappTemplate::where('team_id', auth()->user()->currentTeam->id)->get();
    }

    public function getTagsProperty()
    {
        return \App\Models\ContactTag::where('team_id', auth()->user()->currentTeam->id)->get();
    }

    public function updatedSelectedTags()
    {
        $this->calculateAudience();
    }

    public function updatedAudienceType()
    {
        $this->calculateAudience();
    }

    public function calculateAudience()
    {
        $query = \App\Models\Contact::where('team_id', auth()->user()->currentTeam->id);

        if ($this->audienceType === 'tags' && !empty($this->selectedTags)) {
            $query->whereHas('tags', function ($q) {
                $q->whereIn('contact_tags.id', $this->selectedTags);
            });
        }

        $this->audienceCount = $query->count();
    }

    public function launch()
    {
        $this->validate([
            'name' => 'required',
            'selectedTemplateId' => 'required',
            'audienceCount' => 'numeric|min:1',
            'scheduled_at' => $this->scheduleMode === 'later' ? 'required|date|after:now' : 'nullable'
        ]);

        if ($this->scheduleMode === 'now') {
            $this->scheduled_at = now();
        }

        $template = \App\Models\WhatsappTemplate::find($this->selectedTemplateId);

        // Prepare variables (Prepend Media Link if exists)
        $finalVars = $this->templateVars;
        if (!empty($this->headerMediaUrl)) {
            array_unshift($finalVars, $this->headerMediaUrl);
        }
        // Ensure array is indexed correctly for JSON storage
        $finalVars = array_values($finalVars);

        $campaign = \App\Models\Campaign::create([
            'team_id' => auth()->user()->currentTeam->id,
            'name' => $this->name,
            'campaign_name' => $this->name,
            'template_id' => $template->id,
            'template_name' => $template->name,
            'template_language' => $template->language,
            'template_variables' => $finalVars,
            'audience_filters' => [
                'type' => $this->audienceType,
                'tags' => $this->selectedTags,
                'all' => $this->audienceType === 'all'
            ],
            'scheduled_at' => $this->scheduled_at,
            'status' => 'scheduled'
        ]);

        // Dispatch Job
        // If scheduled for future, we might use Laravel Scheduler to pick it up?
        // Or using `dispatch()->delay(...)`.
        $delay = \Carbon\Carbon::parse($this->scheduled_at);
        $seconds = now()->diffInSeconds($delay, false);
        $delaySeconds = $seconds > 0 ? $seconds : 0;

        \App\Jobs\ProcessCampaignJob::dispatch($campaign->id)->delay($delaySeconds);

        return redirect()->route('campaigns.index')->with('message', 'Campaign Launched!');
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.campaigns.wizard');
    }
}
