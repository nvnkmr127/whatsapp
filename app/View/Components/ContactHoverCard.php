<?php

namespace App\View\Components;

use App\Models\Contact;
use App\Services\ContactTimelineService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class ContactHoverCard extends Component
{
    public $contact;

    public $timeline;

    public $mediaVault;

    public $heatmap;

    public $directMessages;

    public $loadDetails;

    public function __construct(Contact $contact, bool $loadDetails = false)
    {
        $this->contact = $contact;
        $this->loadDetails = $loadDetails;

        $this->timeline = collect();
        $this->mediaVault = collect();
        $this->heatmap = [];
        $this->directMessages = collect();
    }

    protected function loadDetails()
    {
        $timelineService = app(ContactTimelineService::class);
        $this->timeline = $timelineService->getTimeline($this->contact, ! Auth::user()?->is_super_admin)->take(10);
        $this->mediaVault = $timelineService->getMediaVault($this->contact)->take(6);
        $this->heatmap = $timelineService->getInteractionHeatmap($this->contact);
        $this->directMessages = $this->contact->messages()->latest()->take(10)->get();
    }

    public function render()
    {
        if ($this->loadDetails) {
            $this->loadDetails();
        }

        return view('components.contact-hover-card', [
            'contact' => $this->contact,
            'timeline' => $this->timeline,
            'mediaVault' => $this->mediaVault,
            'heatmap' => $this->heatmap,
            'directMessages' => $this->directMessages,
        ]);
    }
}
