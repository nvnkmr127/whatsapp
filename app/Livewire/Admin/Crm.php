<?php

namespace App\Livewire\Admin;

use App\Models\Team;
use App\Models\User;
use App\Services\OfferAuditService;
use App\Services\OfferEligibilityService;
use App\Services\TrialOverrideService;
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

    // ── Override modal state ───────────────────────────────────────────
    /** Number of days to extend trial (used in extend modal) */
    public int $extendDays = 30;
    /** Admin-supplied reason for every override action */
    public string $overrideReason = '';
    /** Plan name for manual conversion */
    public string $convertPlan = 'starter';
    /** Confirmation text for revoke (must type team name) */
    public string $revokeConfirmation = '';

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

    // ------------------------------------------------------------------
    // Trial Override Actions (all delegate to TrialOverrideService)
    // ------------------------------------------------------------------

    /**
     * Force-assign a trial, bypassing eligibility rules.
     * Requires an explicit reason.
     */
    public function forceTrial(int $teamId): void
    {
        if (!Auth::user()->isSuperAdmin())
            abort(403);

        if (empty(trim($this->overrideReason))) {
            session()->flash('error', 'A reason is required for forced trial assignment.');
            return;
        }

        $team = Team::findOrFail($teamId);
        $result = app(TrialOverrideService::class)->forceTrial(
            team: $team,
            admin: Auth::user(),
            months: null, // uses offer_trial_months setting
            reason: $this->overrideReason,
        );

        $this->overrideReason = '';
        session()->flash($result['success'] ? 'message' : 'error', $result['message']);
    }

    /**
     * Extend an existing trial by $extendDays days.
     */
    public function extendTrial(int $teamId): void
    {
        if (!Auth::user()->isSuperAdmin())
            abort(403);

        $team = Team::findOrFail($teamId);
        $result = app(TrialOverrideService::class)->extendTrial(
            team: $team,
            admin: Auth::user(),
            days: max(1, (int) $this->extendDays),
            reason: $this->overrideReason,
        );

        $this->extendDays = 30;
        $this->overrideReason = '';
        session()->flash($result['success'] ? 'message' : 'error', $result['message']);
    }

    /**
     * Immediately revoke a trial. Requires the admin to type the team name
     * as confirmation to prevent accidental revocations.
     */
    public function revokeTrial(int $teamId): void
    {
        if (!Auth::user()->isSuperAdmin())
            abort(403);

        $team = Team::findOrFail($teamId);

        if (strtolower(trim($this->revokeConfirmation)) !== strtolower($team->name)) {
            session()->flash('error', "Revocation cancelled: confirmation text does not match team name '{$team->name}'.");
            return;
        }

        if (empty(trim($this->overrideReason))) {
            session()->flash('error', 'A reason is required for trial revocation.');
            return;
        }

        $result = app(TrialOverrideService::class)->revokeTrial(
            team: $team,
            admin: Auth::user(),
            reason: $this->overrideReason,
        );

        $this->revokeConfirmation = '';
        $this->overrideReason = '';
        session()->flash($result['success'] ? 'message' : 'error', $result['message']);
    }

    /**
     * Manually convert a team to active paid status.
     */
    public function convertToActive(int $teamId): void
    {
        if (!Auth::user()->isSuperAdmin())
            abort(403);

        $team = Team::findOrFail($teamId);
        $result = app(TrialOverrideService::class)->convertToActive(
            team: $team,
            admin: Auth::user(),
            plan: $this->convertPlan ?: null,
            reason: $this->overrideReason,
        );

        $this->overrideReason = '';
        session()->flash($result['success'] ? 'message' : 'error', $result['message']);
    }

    /**
     * Legacy: kept for backward compatibility with any existing Blade calls.
     * Delegates to forceTrial or convertToActive based on current status.
     *
     * @deprecated  Use forceTrial() / convertToActive() instead.
     */
    public function toggleOffer($teamId): void
    {
        $team = Team::findOrFail($teamId);

        if ($team->subscription_status === 'trial') {
            $this->overrideReason = 'Legacy toggleOffer → promote';
            $this->convertToActive((int) $teamId);
        } else {
            $this->overrideReason = 'Legacy toggleOffer → force trial';
            $this->forceTrial((int) $teamId);
        }
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
