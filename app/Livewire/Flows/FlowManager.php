<?php

namespace App\Livewire\Flows;

use App\Models\WhatsAppFlow;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Title;

#[Title('Flows')]
class FlowManager extends Component
{
    public $flows = [];
    public $showCreateModal = false;

    public function mount()
    {
        $this->loadFlows();
    }

    public function loadFlows()
    {
        $this->flows = WhatsAppFlow::where('team_id', Auth::user()->currentTeam->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public $name;
    public $category = 'OTHER';
    public $usesDataEndpoint = true; // Default to true

    public function createFlow()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
        ]);

        $flow = WhatsAppFlow::create([
            'team_id' => Auth::user()->currentTeam->id,
            'name' => $this->name,
            'category' => $this->category,
            'status' => 'DRAFT',
            'uses_data_endpoint' => $this->usesDataEndpoint,
            'design_data' => ['screens' => []],
        ]);

        return redirect()->route('flows.builder', ['flowId' => $flow->id]);
    }

    public function deleteFlow($id)
    {
        $flow = WhatsAppFlow::where('team_id', Auth::user()->currentTeam->id)->findOrFail($id);

        // Integrity Check: Ensure Flow is not used in any active Template
        if ($flow->flow_id) {
            $inUse = \App\Models\WhatsappTemplate::where('team_id', $flow->team_id)
                ->where('components', 'LIKE', "%{$flow->flow_id}%")
                ->exists();

            if ($inUse) {
                // Determine which templates (optional, for better UX)
                // $names = ...
                session()->flash('error', 'Cannot delete Flow: It is currently linked to one or more Templates. Please remove the flow reference from your templates first.');
                return;
            }
        }

        $flow->delete();
        $this->loadFlows();
        session()->flash('success', 'Flow deleted successfully.');
    }

    public function syncFlows()
    {
        try {
            $service = new \App\Services\WhatsAppFlowService();
            $service->setTeam(Auth::user()->currentTeam);
            $flows = $service->getFlowsFromMeta();

            $count = 0;
            foreach ($flows as $metaFlow) {
                // Update based on flow_id
                $wFlow = WhatsAppFlow::updateOrCreate(
                    [
                        'team_id' => Auth::user()->currentTeam->id,
                        'flow_id' => $metaFlow['id']
                    ],
                    [
                        'name' => $metaFlow['name'],
                        'status' => $metaFlow['status'],
                        'category' => $metaFlow['categories'][0] ?? 'OTHER',
                    ]
                );

                // Fetch Design JSON if missing or invalid
                if (empty($wFlow->design_data) || empty($wFlow->design_data['screens'])) {
                    try {
                        $metaJson = $service->getFlowJson($metaFlow['id']);
                        if ($metaJson) {
                            $internalDesign = $service->convertMetaToInternal($metaJson);
                            $wFlow->design_data = $internalDesign;
                            $wFlow->save();
                        } else {
                            // Init empty if no JSON found (e.g. DRAFT with no screens)
                            if ($wFlow->wasRecentlyCreated) {
                                $wFlow->design_data = ['screens' => []];
                                $wFlow->save();
                            }
                        }
                    } catch (\Exception $e) {
                        // Log error but allow sync to continue
                        // Log::error("Failed to sync design for flow {$metaFlow['id']}: " . $e->getMessage());
                    }
                }

                if ($wFlow->wasRecentlyCreated) {
                    $count++;
                }
            }

            $this->loadFlows();
            session()->flash('success', "Synced successfully! Imported {$count} new flows.");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.flows.flow-manager');
    }
}
