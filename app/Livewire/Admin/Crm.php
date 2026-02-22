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

    // ── CRM Activity & Tasks ───────────────────────────────────────────
    public $activityType = 'note';
    public $activityContent = '';
    public $activityOutcome = '';
    public $activityDuration = null;

    public $taskTitle = '';
    public $taskDescription = '';
    public $taskDueDate = '';
    public $taskPriority = 'medium';
    public $taskAssignee = null;

    // ── Advanced Filters & Segments ────────────────────────────────────
    public $showFilters = false;
    public $filterRevenueMin;
    public $filterRevenueMax;
    public $filterDateStart;
    public $filterDateEnd;
    public $filterLeadScoreMin;
    public $segmentName = '';

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
        $this->reset(['activityType', 'activityContent', 'activityOutcome', 'activityDuration', 'taskTitle', 'taskDescription', 'taskDueDate', 'taskPriority', 'taskAssignee']);
    }

    public function addActivity()
    {
        $this->validate([
            'activityType' => 'required',
            'activityContent' => 'required',
        ]);

        $team = Team::find($this->selectedTeamId);
        
        $team->crmActivities()->create([
            'type' => $this->activityType,
            'content' => $this->activityContent,
            'outcome' => $this->activityOutcome,
            'duration_minutes' => $this->activityDuration,
            'performed_at' => now(),
            'user_id' => Auth::id(),
        ]);

        $this->reset(['activityType', 'activityContent', 'activityOutcome', 'activityDuration']);
        session()->flash('message', 'Activity logged.');
    }

    public function addTask()
    {
        $this->validate([
            'taskTitle' => 'required',
        ]);

        $team = Team::find($this->selectedTeamId);

        $team->crmTasks()->create([
            'title' => $this->taskTitle,
            'description' => $this->taskDescription,
            'due_date' => $this->taskDueDate ?: null,
            'priority' => $this->taskPriority,
            'assigned_to_id' => $this->taskAssignee ?: Auth::id(),
            'created_by_id' => Auth::id(),
        ]);

        $this->reset(['taskTitle', 'taskDescription', 'taskDueDate', 'taskPriority', 'taskAssignee']);
        session()->flash('message', 'Task created.');
    }

    public function completeTask($taskId)
    {
        $task = \App\Models\CrmTask::find($taskId);
        if ($task) {
            $task->update(['status' => 'completed', 'completed_at' => now()]);
            session()->flash('message', 'Task completed.');
        }
    }

    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }
    
    public function saveSegment()
    {
        $this->validate(['segmentName' => 'required']);
        
        $filters = [
            'revenue_min' => $this->filterRevenueMin,
            'revenue_max' => $this->filterRevenueMax,
            'date_start' => $this->filterDateStart,
            'date_end' => $this->filterDateEnd,
            'lead_score_min' => $this->filterLeadScoreMin,
            'status' => $this->statusFilter,
            'setup' => $this->setupFilter,
            'funnel' => $this->funnelStage,
        ];
        
        Auth::user()->createdCrmSegments()->create([
            'name' => $this->segmentName,
            'filters' => array_filter($filters), // Remove nulls
        ]);
        
        $this->segmentName = '';
        session()->flash('message', 'Segment saved.');
    }
    
    public function loadSegment($segmentId)
    {
        $segment = \App\Models\CrmSegment::find($segmentId);
        if ($segment) {
            $filters = $segment->filters;
            $this->filterRevenueMin = $filters['revenue_min'] ?? null;
            $this->filterRevenueMax = $filters['revenue_max'] ?? null;
            $this->filterDateStart = $filters['date_start'] ?? null;
            $this->filterDateEnd = $filters['date_end'] ?? null;
            $this->filterLeadScoreMin = $filters['lead_score_min'] ?? null;
            $this->statusFilter = $filters['status'] ?? '';
            $this->setupFilter = $filters['setup'] ?? '';
            $this->funnelStage = $filters['funnel'] ?? '';
        }
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

        if ($this->filterRevenueMin) $query->where('total_revenue', '>=', $this->filterRevenueMin);
        if ($this->filterRevenueMax) $query->where('total_revenue', '<=', $this->filterRevenueMax);
        if ($this->filterLeadScoreMin) $query->where('lead_score', '>=', $this->filterLeadScoreMin);
        if ($this->filterDateStart) $query->whereDate('created_at', '>=', $this->filterDateStart);
        if ($this->filterDateEnd) $query->whereDate('created_at', '<=', $this->filterDateEnd);

        return view('livewire.admin.crm', [
            'teams' => $query->paginate(15),
            'segments' => \App\Models\CrmSegment::where('user_id', Auth::id())->orWhere('is_shared', true)->get(),
            'selectedTeam' => $this->selectedTeamId ? Team::with([
                'owner',
                'users',
                'messages' => function ($q) {
                    $q->latest()->limit(10);
                },
                'crmActivities' => function ($q) {
                    $q->with('user')->latest();
                },
                'crmTasks' => function ($q) {
                    $q->with('assignedTo')->latest();
                }
            ])->find($this->selectedTeamId) : null,
        ]);
    }
}
