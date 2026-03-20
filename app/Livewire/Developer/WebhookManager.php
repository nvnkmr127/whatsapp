<?php

namespace App\Livewire\Developer;

use App\Models\WebhookSubscription;
use Livewire\Component;
use Livewire\WithPagination;

class WebhookManager extends Component
{
    use WithPagination;

    public $name, $url, $secret, $events = [], $is_active = true, $is_system = false;
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
        'auth.otp.login' => 'Auth: Login OTP Sent',
        'otp.sent' => 'OTP Sent',
        'otp.verified' => 'OTP Verified',
        'otp.failed' => 'OTP Verification Failed',
        'billing.threshold_reached' => 'Billing Threshold Reached',
    ];

    public function getFilteredEventsProperty()
    {
        $events = $this->availableEvents;

        if (!auth()->user()->isSuperAdmin()) {
            unset($events['auth.otp.login']);
        }

        return $events;
    }

    protected $rules = [
        'name' => 'required|string|max:255',
        'url' => 'required|url',
        'secret' => 'nullable|string|min:16',
        'events' => 'nullable|array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function create()
    {
        $this->validate();

        // Security check for system-only events
        if (!empty($this->events) && in_array('auth.otp.login', $this->events) && !auth()->user()->isSuperAdmin()) {
            $this->events = array_diff($this->events, ['auth.otp.login']);
        }

        $team = auth()->user()->currentTeam;

        WebhookSubscription::create([
            'team_id' => $team->id,
            'name' => $this->name,
            'url' => $this->url,
            'secret' => $this->secret,
            'events' => empty($this->events) ? null : $this->events,
            'is_active' => $this->is_active,
            'is_system' => $this->is_system || (!empty($this->events) && in_array('auth.otp.login', $this->events)),
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
        $this->is_system = $subscription->is_system;
    }

    public function update()
    {
        $this->validate();

        // Security check for system-only events
        if (!empty($this->events) && in_array('auth.otp.login', $this->events) && !auth()->user()->isSuperAdmin()) {
            $this->events = array_diff($this->events, ['auth.otp.login']);
        }

        $subscription = WebhookSubscription::findOrFail($this->editingId);
        $this->authorize('update', $subscription);

        $subscription->update([
            'name' => $this->name,
            'url' => $this->url,
            'secret' => $this->secret,
            'events' => empty($this->events) ? null : $this->events,
            'is_active' => $this->is_active,
            'is_system' => $this->is_system || (!empty($this->events) && in_array('auth.otp.login', $this->events)),
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

    public function rotateSecret($id)
    {
        $subscription = WebhookSubscription::findOrFail($id);
        $this->authorize('update', $subscription);

        $newSecret = bin2hex(random_bytes(32));
        $subscription->update(['secret' => $newSecret]);

        // If we are currently editing THIS subscription, update the model property too
        if ($this->editingId == $id) {
            $this->secret = $newSecret;
        }

        $this->dispatch('notify', 'Webhook signing secret rotated successfully.');
        audit('webhook.secret_rotated', "Secret rotated for webhook: {$subscription->name} by " . auth()->user()->name);
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
        $user = auth()->user();
        $team = $user->currentTeam;

        $query = WebhookSubscription::query()->with('deliveries');

        if ($team && !$user->isSuperAdmin()) {
            $query->where('team_id', $team->id);
        }

        $subscriptions = $query->latest()->paginate(10);

        return view('livewire.developer.webhook-manager', compact('subscriptions'));
    }
}
