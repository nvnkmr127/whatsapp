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

        // We'll lazy-load these in the view or using separate queries only when the modal is triggerable
        // For now, let's just make them available as empty or minimal collections to prevent crash
        $this->timeline = collect();
        $this->mediaVault = collect();
        $this->heatmap = [];
        $this->directMessages = collect();
    }

    protected function loadDetails()
    {
        $timelineService = app(ContactTimelineService::class);
        $this->timeline = $timelineService->getTimeline($this->contact, ! auth()->user()?->is_super_admin)->take(10);
        $this->mediaVault = $timelineService->getMediaVault($this->contact)->take(6);
        $this->heatmap = $timelineService->getInteractionHeatmap($this->contact);
        $this->directMessages = $this->contact->messages()->latest()->take(10)->get();
    }

    public function render()
    {
        // Load only when rendering the full card (this is still called per row, but we can potentially optimize)
        // A better fix would be a separate Livewire component for the modal.
        $this->loadDetails();

        return view('components.contact-hover-card', [
            'contact' => $this->contact,
            'timeline' => $this->timeline,
            'mediaVault' => $this->mediaVault,
            'heatmap' => $this->heatmap,
            'directMessages' => $this->directMessages,
        ]);
    }
}
