<?php

namespace App\Livewire\Templates;

use App\Models\WhatsappTemplate;
use App\Traits\WhatsApp;
use Livewire\Component;
use Livewire\WithPagination;

class TemplateList extends Component
{
    use WhatsApp;
    use WithPagination;

    public $search = '';
    public $syncing = false;

    protected $listeners = ['refreshComponent' => '$refresh'];

    public function syncTemplates()
    {
        $this->syncing = true;
        // Check if settings allow sync (for now assuming yes or handling gracefully)
        $response = $this->loadTemplatesFromWhatsApp();

        if ($response['status']) {
            $this->dispatch('notify', 'Templates synced: ' . ($response['count'] ?? 0));
        } else {
            $this->dispatch('notify', 'Sync failed: ' . ($response['message'] ?? 'Unknown error'));
        }
        $this->syncing = false;
    }

    public function render()
    {
        $query = WhatsappTemplate::query();

        if ($this->search) {
            $query->where('template_name', 'like', '%' . $this->search . '%')
                ->orWhere('category', 'like', '%' . $this->search . '%');
        }

        $templates = $query->latest()->paginate(10);

        return view('livewire.templates.template-list', [
            'templates' => $templates
        ]);
    }
}
