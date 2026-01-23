<?php

namespace App\Livewire\Settings;

use App\Models\CannedMessage;
use Livewire\Component;
use Livewire\WithPagination;

class CannedMessageManager extends Component
{
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $confirmingDeletion = false;
    public $messageIdBeingDeleted;

    // Form inputs
    public $cannedMessageId;
    public $shortcut;
    public $content;

    protected $rules = [
        'shortcut' => 'nullable|string|max:50',
        'content' => 'required|string|max:1000',
    ];

    public function render()
    {
        $query = CannedMessage::where('team_id', auth()->user()->currentTeam->id);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('shortcut', 'like', '%' . $this->search . '%')
                    ->orWhere('content', 'like', '%' . $this->search . '%');
            });
        }

        return view('livewire.settings.canned-message-manager', [
            'messages' => $query->latest()->paginate(10),
        ]);
    }

    public function openModal()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-settings');
        $this->reset(['cannedMessageId', 'shortcut', 'content']);
        $this->showModal = true;
    }

    public function edit($id)
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-settings');
        $message = CannedMessage::where('team_id', auth()->user()->currentTeam->id)->findOrFail($id);
        $this->cannedMessageId = $message->id;
        $this->shortcut = $message->shortcut;
        $this->content = $message->content;
        $this->showModal = true;
    }

    public function save()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-settings');
        $this->validate();

        // Check for duplicate shortcut if provided
        if ($this->shortcut) {
            $exists = CannedMessage::where('team_id', auth()->user()->currentTeam->id)
                ->where('shortcut', $this->shortcut)
                ->where('id', '!=', $this->cannedMessageId)
                ->exists();

            if ($exists) {
                $this->addError('shortcut', 'This shortcut is already used.');
                return;
            }
        }

        CannedMessage::updateOrCreate(
            ['id' => $this->cannedMessageId],
            [
                'team_id' => auth()->user()->currentTeam->id,
                'shortcut' => $this->shortcut,
                'content' => $this->content,
            ]
        );

        $this->showModal = false;
        $this->dispatch('notify', message: 'Canned message saved successfully!', type: 'success');
    }

    public function confirmDelete($id)
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-settings');
        $this->messageIdBeingDeleted = $id;
        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-settings');
        $message = CannedMessage::where('team_id', auth()->user()->currentTeam->id)->findOrFail($this->messageIdBeingDeleted);
        $message->delete();

        $this->confirmingDeletion = false;
        $this->dispatch('notify', message: 'Canned message deleted.', type: 'success');
    }
}
