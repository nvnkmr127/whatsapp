<?php

namespace App\Livewire\Chat;

use App\Models\Category;
use App\Models\Conversation;
use App\Services\ContactTimelineService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ContactDetails extends Component
{
    public $conversationId;

    public $conversation;

    public $contact;

    public $timeline = [];

    public $mediaVault = [];

    public $heatmap = [];

    public $activeTab = 'timeline'; // Default tab

    public $newNoteBody = '';

    protected $listeners = ['refresh-tags' => 'loadData'];

    public function mount($conversationId)
    {
        $this->conversationId = $conversationId;
        $this->loadData();
    }

    public function loadData(?ContactTimelineService $timelineService = null)
    {
        // For Livewire lifecycle compatibility, we fall back to app() if not passed during first run
        $timelineService = $timelineService ?? app(ContactTimelineService::class);

        $this->conversation = Conversation::with([
            'contact.tags',
            'contact.attributedMessages.attributedCampaign',
            'notes.user',
            'assignee',
        ])->find($this->conversationId);

        if ($this->conversation) {
            $this->contact = $this->conversation->contact;
            if ($this->contact) {
                $this->timeline = $timelineService->getTimeline($this->contact, ! Auth::user()?->is_super_admin);
                $this->mediaVault = $timelineService->getMediaVault($this->contact);
                $this->heatmap = $timelineService->getInteractionHeatmap($this->contact);
            } else {
                $this->timeline = [];
                $this->mediaVault = [];
                $this->heatmap = [];
            }
        } else {
            $this->contact = null;
            $this->timeline = [];
            $this->mediaVault = [];
            $this->heatmap = [];
        }
    }

    public function assignToSelf()
    {
        if ($this->conversation) {
            $this->conversation->update(['assigned_to' => auth()->id()]);
            $this->loadData();
        }
    }

    public function unassign()
    {
        if ($this->conversation) {
            $this->conversation->update(['assigned_to' => null]);
            $this->loadData();
        }
    }

    public function addNote()
    {
        $this->validate(['newNoteBody' => 'required|string|max:1000']);

        if ($this->conversation) {
            $this->conversation->notes()->create([
                'team_id' => auth()->user()->currentTeam->id,
                'user_id' => auth()->id(),
                'content' => $this->newNoteBody,
            ]);

            $this->newNoteBody = '';
            $this->loadData();
        }
    }

    public function toggleOptIn(\App\Services\ConsentService $consentService)
    {
        if (! $this->conversation || ! $this->contact) {
            return;
        }

        if ($this->contact->opt_in_status === 'opted_in') {
            $consentService->optOut($this->contact, 'MANUAL_AGENT', 'Agent toggled status in chat interface.');
        } else {
            $consentService->optIn($this->contact, 'MANUAL_AGENT', 'Agent toggled status in chat interface.');
        }

        $this->loadData();
    }

    public function toggleConversationTag($categoryId)
    {
        if (! $this->conversation) {
            return;
        }

        $this->conversation->refresh();
        $metadata = $this->conversation->metadata;
        if (! is_array($metadata)) {
            $metadata = [];
        }
        $tags = $metadata['tags'] ?? [];

        if (in_array($categoryId, $tags)) {
            $tags = array_values(array_filter($tags, fn ($id) => $id != $categoryId));
        } else {
            $tags[] = (int) $categoryId;
        }

        $metadata['tags'] = $tags;
        $this->conversation->update(['metadata' => $metadata]);

        $this->dispatch('refresh-tags');
        $this->loadData();
    }

    #[Computed]
    public function availableTags()
    {
        $conversationTags = $this->conversation?->metadata['tags'] ?? [];

        return Category::where('team_id', auth()->user()->currentTeam->id)
            ->whereIn('target_module', ['chat', 'all'])
            ->where('is_active', true)
            ->whereNotIn('id', $conversationTags)
            ->get();
    }

    #[Computed]
    public function activeTags()
    {
        $tagIds = $this->conversation?->metadata['tags'] ?? [];
        if (empty($tagIds)) {
            return collect();
        }

        return Category::whereIn('id', $tagIds)->get();
    }

    public function render()
    {
        return view('livewire.chat.contact-details');
    }
}
