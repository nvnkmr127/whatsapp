<?php

namespace App\Livewire\Chat;

use Livewire\Component;
use Livewire\WithFileUploads;

class MediaUpload extends Component
{
    use WithFileUploads;

    public $conversationId;

    public $newAttachment;

    public $storedPath = null;

    public $uploadError = null;

    protected $listeners = [
        'triggerMediaUpload' => 'openFileDialog',
        'clearMediaUpload' => 'resetAfterSend',
    ];

    public function openFileDialog()
    {
        $this->dispatch('open-file-input');
    }

    public function updatedNewAttachment()
    {
        $this->uploadError = null;

        try {
            $this->validate([
                'newAttachment' => 'max:16384', // 16MB limit
            ]);

            // Persist to the public disk so SendMessageJob (and Meta, which fetches the
            // link) have a stable URL. The Livewire temporary URL expires and is not
            // reachable by Meta, so the media MUST be stored before sending.
            $this->storedPath = $this->newAttachment->store('chat-media', 'public');

            $this->dispatch('mediaUploaded', [
                'name' => $this->newAttachment->getClientOriginalName(),
                'mime_type' => $this->newAttachment->getMimeType(),
                'size' => $this->newAttachment->getSize(),
                'path' => $this->storedPath,
            ]);
        } catch (\Exception $e) {
            $this->uploadError = 'File upload failed: '.$e->getMessage();
            $this->newAttachment = null;
        }
    }

    public function deleteAttachment()
    {
        // Cancelled before sending — remove the orphaned file.
        if ($this->storedPath) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($this->storedPath);
            $this->storedPath = null;
        }
        $this->newAttachment = null;
        $this->dispatch('mediaDeleted');
    }

    /**
     * After a successful send the Message row owns the file — clear the picker
     * WITHOUT deleting the stored file.
     */
    public function resetAfterSend()
    {
        $this->newAttachment = null;
        $this->storedPath = null;
    }

    public function render()
    {
        return view('livewire.chat.media-upload');
    }
}
