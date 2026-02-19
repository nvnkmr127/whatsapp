<?php

namespace App\Livewire\Admin;

use App\Models\Team;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class Crm extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $setupFilter = '';
    public $funnelStage = '';
    public $showOfferOnly = false;

    public $stats = [];
    public $funnel = [];
    public $selectedTeamId = null;

    protected $queryString = ['search', 'statusFilter', 'setupFilter', 'funnelStage', 'showOfferOnly'];

    public function mount()
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403);
        }
        $this->loadStats();
    }

    public function loadStats()
    {
        $this->stats = [
            'total_users' => User::count(),
            'new_today' => User::whereDate('created_at', now()->today())->count(),
            'new_this_week' => User::where('created_at', '>=', now()->startOfWeek())->count(),
            'active_setups' => Team::where('whatsapp_setup_state', 'ready')->orWhere('whatsapp_setup_state', 'ACTIVE')->count(),
            'trial_users' => Team::where('subscription_status', 'trial')->count(),
        ];

        $this->funnel = [
            'total' => Team::count(),
            'connected_fb' => Team::whereNotNull('whatsapp_access_token')->count(),
            'waba_found' => Team::whereNotNull('whatsapp_business_account_id')->whereNotNull('whatsapp_access_token')->count(),
            'phone_registered' => Team::whereNotNull('whatsapp_phone_number_id')->count(),
            'active_messaging' => Team::whereHas('messages', function ($q) {
                $q->where('direction', 'outbound');
            })->count(),
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function setFunnel($stage)
    {
        $this->funnelStage = ($this->funnelStage === $stage) ? '' : $stage;
        $this->resetPage();
    }

    public $adminNote = '';

    public function showUser($teamId)
    {
        $this->selectedTeamId = $teamId;
        $team = Team::find($teamId);
        $this->adminNote = $team->admin_notes ?? '';
    }

    public function closeUser()
    {
        $this->selectedTeamId = null;
    }

    public function impersonate($userId)
    {
        return redirect()->route('admin.impersonate.enter', ['user' => $userId]);
    }

    public function toggleOffer($teamId)
    {
        $team = Team::findOrFail($teamId);
        if ($team->subscription_status === 'trial') {
            $team->update([
                'subscription_status' => 'active',
                'trial_ends_at' => null
            ]);
        } else {
            $team->update([
                'subscription_status' => 'trial',
                'trial_ends_at' => now()->addMonths(6)
            ]);
        }
        session()->flash('message', 'Offer toggled for ' . $team->name);
    }

    public function saveNote()
    {
        if ($this->selectedTeamId) {
            $team = Team::find($this->selectedTeamId);
            $team->update(['admin_notes' => $this->adminNote]);
            session()->flash('success', 'Admin notes updated.');
        }
    }

    public function updateLeadScores()
    {
        $teams = Team::all();
        foreach ($teams as $team) {
            $team->update(['lead_score' => $team->calculateLeadScore()]);
        }
        session()->flash('message', 'All lead scores recalculated.');
    }

    public function exportCsv()
    {
        $query = Team::with(['owner'])->latest();

        // Apply same filters as render()
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhereHas('owner', function ($sq) {
                        $sq->where('email', 'like', '%' . $this->search . '%')
                            ->orWhere('name', 'like', '%' . $this->search . '%');
                    });
            });
        }
        if ($this->statusFilter)
            $query->where('subscription_status', $this->statusFilter);
        if ($this->setupFilter)
            $query->where('whatsapp_setup_state', $this->setupFilter);
        if ($this->funnelStage) {
            match ($this->funnelStage) {
                'connected_fb' => $query->whereNotNull('whatsapp_access_token'),
                'waba_found' => $query->whereNotNull('whatsapp_business_account_id')->whereNotNull('whatsapp_access_token'),
                'phone_registered' => $query->whereNotNull('whatsapp_phone_number_id'),
                'active_messaging' => $query->whereHas('messages', function ($q) {
                        $q->where('direction', 'outbound');
                    }),
                default => null
            };
        }

        $teams = $query->get();
        $filename = "crm_export_" . now()->format('Y_m_d_His') . ".csv";
        $handle = fopen('php://output', 'w');

        // Add headers
        fputcsv($handle, ['Team Name', 'Owner', 'Email', 'Status', 'Setup Stage', 'Lead Score', 'LTV (Revenue)', 'Signup Date']);

        foreach ($teams as $team) {
            fputcsv($handle, [
                $team->name,
                $team->owner->name,
                $team->owner->email,
                $team->subscription_status,
                $this->getSetupLabel($team),
                $team->lead_score,
                $team->total_revenue,
                $team->created_at->format('Y-m-d H:i')
            ]);
        }

        return response()->stream(function () use ($handle) {
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    protected function getSetupLabel($team)
    {
        if ($team->last_webhook_received_at)
            return 'Deployed';
        if ($team->whatsapp_phone_number_id)
            return 'Ready';
        if ($team->whatsapp_business_account_id)
            return 'Config';
        if ($team->whatsapp_access_token)
            return 'Auth';
        return 'Zero';
    }

    public function render()
    {
        $query = Team::with(['owner', 'users'])->latest();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhereHas('owner', function ($sq) {
                        $sq->where('email', 'like', '%' . $this->search . '%')
                            ->orWhere('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        if ($this->statusFilter) {
            $query->where('subscription_status', $this->statusFilter);
        }

        if ($this->setupFilter) {
            $query->where('whatsapp_setup_state', $this->setupFilter);
        }

        if ($this->showOfferOnly) {
            $query->where('subscription_status', 'trial');
        }

        if ($this->funnelStage) {
            match ($this->funnelStage) {
                'connected_fb' => $query->whereNotNull('whatsapp_access_token'),
                'waba_found' => $query->whereNotNull('whatsapp_business_account_id')->whereNotNull('whatsapp_access_token'),
                'phone_registered' => $query->whereNotNull('whatsapp_phone_number_id'),
                'active_messaging' => $query->whereHas('messages', function ($q) {
                        $q->where('direction', 'outbound');
                    }),
                default => null
            };
        }

        return view('livewire.admin.crm', [
            'teams' => $query->paginate(15),
            'selectedTeam' => $this->selectedTeamId ? Team::with([
                'owner',
                'users',
                'messages' => function ($q) {
                    $q->latest()->limit(10);
                }
            ])->find($this->selectedTeamId) : null,
        ]);
    }
}
