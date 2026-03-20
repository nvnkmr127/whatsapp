<?php

namespace App\Livewire\Chat;

use Livewire\Component;
use Livewire\WithFileUploads;

class MediaUpload extends Component
{
    use WithFileUploads;

    public $conversationId;
    public $newAttachment;
    public $uploadError = null;

    protected $listeners = [
        'triggerMediaUpload' => 'openFileDialog',
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
            
            $this->dispatch('mediaUploaded', [
                'name' => $this->newAttachment->getClientOriginalName(),
                'mime_type' => $this->newAttachment->getMimeType(),
                'size' => $this->newAttachment->getSize(),
                'temp_url' => $this->newAttachment->temporaryUrl(),
            ]);
        } catch (\Exception $e) {
            $this->uploadError = "File upload failed: " . $e->getMessage();
            $this->newAttachment = null;
        }
    }

    public function deleteAttachment()
    {
        $this->newAttachment = null;
        $this->dispatch('mediaDeleted');
    }

    public function render()
    {
        return view('livewire.chat.media-upload');
    }
}
