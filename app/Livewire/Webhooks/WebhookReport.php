<?php

namespace App\Livewire\Webhooks;

use Livewire\Component;

class WebhookReport extends Component
{
    use \Livewire\WithPagination;

    public $workflowId;
    public $workflow;
    public $search = '';

    public function mount($workflowId)
    {
        $this->workflowId = $workflowId;
        $this->workflow = \App\Models\WebhookWorkflow::where('team_id', auth()->user()->currentTeam->id)
            ->with('template')
            ->findOrFail($workflowId);
    }

    public function getStatsProperty()
    {
        // Query to get message stats for this workflow
        $query = \App\Models\Message::where('webhook_workflow_id', $this->workflowId);

        return [
            'total' => (clone $query)->count(),
            'delivered' => (clone $query)->whereIn('status', ['delivered', 'read'])->count(),
            'read' => (clone $query)->where('status', 'read')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'sent' => (clone $query)->where('status', 'sent')->count(), // Processed but not yet delivered
        ];
    }

    public function render()
    {
        $messages = \App\Models\Message::where('webhook_workflow_id', $this->workflowId)
            ->when($this->search, function ($q) {
                $q->where('chat_id', 'like', "%{$this->search}%")
                    ->orWhere('body', 'like', "%{$this->search}%");
            })
            ->latest()
            ->paginate(10);

        return view('livewire.webhooks.webhook-report', [
            'messages' => $messages,
            'stats' => $this->stats
        ]);
    }
}
