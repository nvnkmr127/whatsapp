<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\SlaPolicy;
use App\Services\SlaService;
use Livewire\Component;
use Livewire\Attributes\Computed;

class SlaDashboard extends Component
{
    public int  $days = 30;

    // Policy editor
    public bool   $showPolicyForm   = false;
    public ?int   $editingPolicyId  = null;
    public string $policyName       = 'Default SLA';
    public int    $firstResponseMin = 120;  // 2h
    public int    $resolutionMin    = 1440; // 24h
    public bool   $isDefault        = true;

    #[Computed]
    public function team() { return auth()->user()->currentTeam; }

    #[Computed]
    public function policies()
    {
        return SlaPolicy::where('team_id', $this->team->id)->get();
    }

    #[Computed]
    public function stats(): array
    {
        return app(SlaService::class)->getTeamStats($this->team, $this->days);
    }

    #[Computed]
    public function breachedConversations()
    {
        return Conversation::where('team_id', $this->team->id)
            ->where('sla_status', 'breached')
            ->where('status', 'open')
            ->with(['contact:id,name,phone_number', 'assignee:id,name', 'slaPolicy:id,name'])
            ->latest()
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function warningConversations()
    {
        return Conversation::where('team_id', $this->team->id)
            ->where('sla_status', 'breaching_soon')
            ->where('status', 'open')
            ->with(['contact:id,name,phone_number', 'assignee:id,name'])
            ->latest()
            ->limit(20)
            ->get();
    }

    public function savePolicy(): void
    {
        $this->validate([
            'policyName'       => 'required|string|max:80',
            'firstResponseMin' => 'required|integer|min:0',
            'resolutionMin'    => 'required|integer|min:0',
        ]);

        $team = $this->team;

        if ($this->isDefault) {
            // Remove default flag from all other policies for this team
            SlaPolicy::where('team_id', $team->id)->update(['is_default' => false]);
        }

        SlaPolicy::updateOrCreate(
            ['id' => $this->editingPolicyId],
            [
                'team_id'                => $team->id,
                'name'                   => $this->policyName,
                'first_response_minutes' => $this->firstResponseMin,
                'resolution_minutes'     => $this->resolutionMin,
                'is_default'             => $this->isDefault,
            ]
        );

        $this->dispatch('notify', title: 'SLA Policy Saved', type: 'success');
        $this->resetPolicyForm();
        unset($this->policies); // clear computed cache
    }

    public function editPolicy(int $id): void
    {
        $policy = SlaPolicy::findOrFail($id);
        $this->editingPolicyId  = $policy->id;
        $this->policyName       = $policy->name;
        $this->firstResponseMin = $policy->first_response_minutes;
        $this->resolutionMin    = $policy->resolution_minutes;
        $this->isDefault        = $policy->is_default;
        $this->showPolicyForm   = true;
    }

    public function deletePolicy(int $id): void
    {
        SlaPolicy::where('team_id', $this->team->id)->findOrFail($id)->delete();
        $this->dispatch('notify', title: 'Policy Deleted', type: 'info');
        unset($this->policies);
    }

    public function resetPolicyForm(): void
    {
        $this->showPolicyForm   = false;
        $this->editingPolicyId  = null;
        $this->policyName       = 'Default SLA';
        $this->firstResponseMin = 120;
        $this->resolutionMin    = 1440;
        $this->isDefault        = true;
    }

    public function runCheck(): void
    {
        $result = app(SlaService::class)->checkBreaches($this->team);
        $this->dispatch('notify',
            title: "SLA Check Complete",
            message: "{$result['breached']} breached · {$result['warning']} warnings",
            type: 'info'
        );
        unset($this->breachedConversations, $this->warningConversations, $this->stats);
    }

    public function render()
    {
        return view('livewire.chat.sla-dashboard');
    }
}
