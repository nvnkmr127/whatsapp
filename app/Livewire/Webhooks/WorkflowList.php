<?php

namespace App\Livewire\Webhooks;

use Livewire\Component;

class WorkflowList extends Component
{
    use \Livewire\WithPagination;

    public $showCreateModal = false;
    public $editingId = null;

    // Form Fields
    public $name;
    public $whatsapp_template_id;

    protected $rules = [
        'name' => 'required|min:3',
        'whatsapp_template_id' => 'required|exists:whatsapp_templates,id',
    ];

    public function getTemplatesProperty()
    {
        return \App\Models\WhatsappTemplate::where('team_id', auth()->user()->currentTeam->id)->get();
    }

    public function create()
    {
        $this->validate();

        \App\Models\WebhookWorkflow::create([
            'team_id' => auth()->user()->currentTeam->id,
            'name' => $this->name,
            'whatsapp_template_id' => $this->whatsapp_template_id,
            'status' => true,
        ]);

        $this->reset(['name', 'whatsapp_template_id', 'showCreateModal', 'editingId']);
        $this->dispatch('notify', 'Workflow created successfully.');
    }

    public function edit($id)
    {
        $workflow = \App\Models\WebhookWorkflow::where('team_id', auth()->user()->currentTeam->id)->find($id);
        if ($workflow) {
            $this->editingId = $id;
            $this->name = $workflow->name;
            $this->whatsapp_template_id = $workflow->whatsapp_template_id;
        }
    }

    public function update()
    {
        $this->validate();

        $workflow = \App\Models\WebhookWorkflow::where('team_id', auth()->user()->currentTeam->id)->find($this->editingId);
        if ($workflow) {
            $workflow->update([
                'name' => $this->name,
                'whatsapp_template_id' => $this->whatsapp_template_id,
            ]);

            $this->dispatch('notify', 'Workflow updated successfully.');
            $this->cancelEdit();
        }
    }

    public function cancelEdit()
    {
        $this->reset(['editingId', 'name', 'whatsapp_template_id']);
    }

    public function toggleStatus($id)
    {
        $workflow = \App\Models\WebhookWorkflow::where('team_id', auth()->user()->currentTeam->id)->find($id);
        if ($workflow) {
            $workflow->status = !$workflow->status;
            $workflow->save();
        }
    }

    public function delete($id)
    {
        $workflow = \App\Models\WebhookWorkflow::where('team_id', auth()->user()->currentTeam->id)->find($id);
        if ($workflow) {
            $workflow->delete();
            $this->dispatch('notify', 'Workflow deleted.');
        }
    }

    public function render()
    {
        $workflows = \App\Models\WebhookWorkflow::where('team_id', auth()->user()->currentTeam->id)
            ->with('template')
            ->latest()
            ->paginate(10);

        return view('livewire.webhooks.workflow-list', [
            'workflows' => $workflows
        ]);
    }
}
