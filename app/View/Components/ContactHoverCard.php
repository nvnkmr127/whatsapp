<?php

namespace App\View\Components;

use App\Models\Contact;
use App\Services\ContactTimelineService;
use Illuminate\View\Component;

class ContactHoverCard extends Component
{
    public $contact;
    public $timeline;
    public $mediaVault;
    public $heatmap;
    public $directMessages;

    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
        
        $timelineService = app(ContactTimelineService::class);
        $this->timeline = $timelineService->getTimeline($contact, !auth()->user()?->is_super_admin)->take(10);
        $this->mediaVault = $timelineService->getMediaVault($contact)->take(6);
        $this->heatmap = $timelineService->getInteractionHeatmap($contact);
        $this->directMessages = $contact->messages()->latest()->take(10)->get();
    }

    public function render()
    {
        return view('components.contact-hover-card', [
            'contact' => $this->contact,
            'timeline' => $this->timeline,
            'mediaVault' => $this->mediaVault,
            'heatmap' => $this->heatmap,
            'directMessages' => $this->directMessages,
        ]);
    }
}
