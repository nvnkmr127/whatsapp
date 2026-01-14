<?php

namespace App\Livewire\Developer;

use App\Models\WebhookSubscription;
use Livewire\Component;
use Livewire\WithPagination;

class WebhookManager extends Component
{
    use WithPagination;

    public $name, $url, $secret, $events = [], $is_active = true;
    public $editingId = null;

    public $availableEvents = [
        'message.received' => 'Message Received',
        'message.status_updated' => 'Message Status Updated',
        'message.sent' => 'Message Sent',
        'contact.created' => 'Contact Created',
        'contact.updated' => 'Contact Updated',
        'conversation.started' => 'Conversation Started',
        'conversation.assigned' => 'Conversation Assigned',
        'campaign.completed' => 'Campaign Completed',
        'automation.triggered' => 'Automation Triggered',
    ];

    protected $rules = [
        'name' => 'required|string|max:255',
        'url' => 'required|url',
        'secret' => 'nullable|string|min:16',
        'events' => 'nullable|array',
        'is_active' => 'boolean',
    ];

    public function create()
    {
        $this->validate();

        $team = auth()->user()->currentTeam;

        WebhookSubscription::create([
            'team_id' => $team->id,
            'name' => $this->name,
            'url' => $this->url,
            'secret' => $this->secret,
            'events' => empty($this->events) ? null : $this->events,
            'is_active' => $this->is_active,
        ]);

        $this->reset(['name', 'url', 'secret', 'events', 'is_active']);
        $this->dispatch('notify', 'Webhook subscription created successfully.');
    }

    public function edit($id)
    {
        $subscription = WebhookSubscription::findOrFail($id);
        $this->authorize('update', $subscription);

        $this->editingId = $id;
        $this->name = $subscription->name;
        $this->url = $subscription->url;
        $this->secret = $subscription->secret;
        $this->events = $subscription->events ?? [];
        $this->is_active = $subscription->is_active;
    }

    public function update()
    {
        $this->validate();

        $subscription = WebhookSubscription::findOrFail($this->editingId);
        $this->authorize('update', $subscription);

        $subscription->update([
            'name' => $this->name,
            'url' => $this->url,
            'secret' => $this->secret,
            'events' => empty($this->events) ? null : $this->events,
            'is_active' => $this->is_active,
        ]);

        $this->reset(['editingId', 'name', 'url', 'secret', 'events', 'is_active']);
        $this->dispatch('notify', 'Webhook subscription updated successfully.');
    }

    public function cancelEdit()
    {
        $this->reset(['editingId', 'name', 'url', 'secret', 'events', 'is_active']);
    }

    public function delete($id)
    {
        $subscription = WebhookSubscription::findOrFail($id);
        $this->authorize('delete', $subscription);

        $subscription->delete();
        $this->dispatch('notify', 'Webhook subscription deleted successfully.');
    }

    public function toggleStatus($id)
    {
        $subscription = WebhookSubscription::findOrFail($id);
        $this->authorize('update', $subscription);

        $subscription->update(['is_active' => !$subscription->is_active]);
    }

    public function generateSecret()
    {
        $this->secret = bin2hex(random_bytes(32));
    }

    public function testWebhook($id)
    {
        $subscription = WebhookSubscription::findOrFail($id);
        $this->authorize('view', $subscription);

        $webhookService = new \App\Services\WebhookService();

        $testData = [
            'id' => 999,
            'test' => true,
            'message' => 'This is a test webhook event',
            'timestamp' => now()->toIso8601String(),
        ];

        try {
            $webhookService->dispatch(
                $subscription->team_id,
                'test.event',
                $testData
            );

            $this->dispatch('notify', 'Test webhook sent successfully! Check your endpoint.');
        } catch (\Exception $e) {
            $this->dispatch('notify', 'Test webhook failed: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $team = auth()->user()->currentTeam;

        $subscriptions = WebhookSubscription::where('team_id', $team->id)
            ->with('deliveries')
            ->latest()
            ->paginate(10);

        $workflows = \App\Models\WebhookWorkflow::where('team_id', $team->id)
            ->with('template')
            ->latest()
            ->get();

        return view('livewire.developer.webhook-manager', compact('subscriptions', 'workflows'));
    }
}
