<?php

namespace App\Livewire\Automations\Traits;

use App\Models\Automation;
use Illuminate\Support\Facades\Auth;

trait HasPersistence
{
    public function save($shouldActivate = false)
    {
        $this->logDebug('Save Clicked', [
            'raw_nodes_count' => count($this->nodes),
            'raw_edges_count' => count($this->edges),
            'selected_node' => $this->selectedNodeId,
            'should_activate' => $shouldActivate
        ]);

        $this->updateNodeData();
        $this->runValidation();

        try {
            $this->validate([
                'name' => 'required|string|max:255',
                'nodes' => 'required|array|min:1',
            ], [
                'nodes.required' => 'The automation flow cannot be empty.',
                'nodes.min' => 'Please add at least one node to the automation.',
            ]);

            if ($shouldActivate && !$this->isActivatable) {
                $this->addError('base', 'There are critical errors in your flow. Please fix them before publishing.');
                $this->showErrorModal = true;
                return;
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->showErrorModal = true;
            throw $e;
        }

        try {
            $data = [
                'team_id' => Auth::user()->currentTeam->id,
                'name' => $this->name,
                'is_active' => $shouldActivate ? true : (isset($this->automationId) ? \App\Models\Automation::find($this->automationId)->is_active : false),
                'trigger_type' => $this->triggerType,
                'trigger_config' => $this->triggerConfig,
                'version' => $this->version,
                'last_published_at' => $this->lastPublishedAt,
                'publish_log' => $this->publishLog,
                'flow_data' => [
                    'nodes' => array_values($this->nodes),
                    'edges' => array_values($this->edges)
                ]
            ];

            if ($this->debugMode) {
                $this->logDebug('Final Save Payload', [
                    'id' => $this->automationId,
                    'node_count' => count($this->nodes),
                    'edge_count' => count($this->edges),
                    'payload' => $data['flow_data']
                ]);
            }

            if ($this->automationId) {
                $automation = Automation::where('team_id', Auth::user()->currentTeam->id)->findOrFail($this->automationId);
                $automation->update($data);
                $this->isDirty = false;
                session()->flash('success', $shouldActivate ? 'Automation published successfully!' : 'Draft saved successfully!');
            } else {
                $automation = Automation::create($data);
                $this->automationId = $automation->id;
                $this->isDirty = false;
                session()->flash('success', $shouldActivate ? 'Automation created and published!' : 'Draft created successfully!');
                return redirect()->route('automations.builder', $automation->id);
            }
        } catch (\Exception $e) {
            $this->logDebug('Save Exception', ['error' => $e->getMessage()]);
            $this->addError('base', 'An error occurred while saving the automation.');
            $this->showErrorModal = true;
        }
    }

    public function publish()
    {
        $this->updateNodeData();
        $this->runValidation();

        if (!$this->isActivatable) {
            $this->addError('base', 'There are critical errors in your flow. Please fix them before publishing.');
            $this->showErrorModal = true;
            return;
        }

        $this->showPublishModal = true;
    }

    public function confirmPublish()
    {
        $this->logDebug('Confirming Publish', ['note' => $this->publishNote]);

        if ($this->automationId) {
            $automation = Automation::where('team_id', Auth::user()->currentTeam->id)->findOrFail($this->automationId);
            $newVersion = ($automation->version ?? 0) + 1;
            $this->version = $newVersion;
        } else {
            $this->version = 1;
        }

        $this->lastPublishedAt = now();

        $entry = [
            'version' => $this->version,
            'note' => $this->publishNote,
            'published_at' => now()->toDateTimeString(),
            'published_by' => Auth::user()->name
        ];

        if (!is_array($this->publishLog)) {
            $this->publishLog = [];
        }
        array_unshift($this->publishLog, $entry);

        $result = $this->save(true);
        if ($result instanceof \Illuminate\Http\RedirectResponse || $result instanceof \Livewire\Features\SupportRedirects\Redirector) {
            return $result;
        }

        $this->showPublishModal = false;
        $this->publishNote = '';
    }
}
